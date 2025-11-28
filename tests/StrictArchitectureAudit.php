<?php

/**
* STRICT MVC-S ARCHITECTURE AUDIT
*
* Enforces the Golden Rules of the StarlightDominion V2 Architecture:
* 1. Separation of Concerns (Controller -> Service -> Repository).
* 2. Service Layer Purity (No Session, No Output).
* 3. View Purity (No Logic, No DB).
* 4. Entity Immutability (DTOs).
*
* Usage: php tests/StrictArchitectureAudit.php
*/

if (php_sapi_name() !== 'cli') {
die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

// --- Configuration ---

$config = [
'colors' => [
'green' => "\033[32m",
'red' => "\033[31m",
'yellow' => "\033[33m",
'reset' => "\033[0m",
'bold' => "\033[1m"
],
'paths' => [
'controllers' => __DIR__ . '/../app/Controllers',
'services' => __DIR__ . '/../app/Models/Services',
'repositories' => __DIR__ . '/../app/Models/Repositories',
'entities' => __DIR__ . '/../app/Models/Entities',
'views' => __DIR__ . '/../views',
]
];

class ArchitectureAuditor
{
private array $violations = [];
private int $filesChecked = 0;
private array $config;

public function __construct(array $config)
{
$this->config = $config;
}

public function run(): void
{
$this->printHeader();

// 1. Audit Controllers
$this->auditLayer(
'Controllers',
$this->config['paths']['controllers'],
[$this, 'checkControllerRules']
);

// 2. Audit Services
$this->auditLayer(
'Services',
$this->config['paths']['services'],
[$this, 'checkServiceRules']
);

// 3. Audit Repositories
$this->auditLayer(
'Repositories',
$this->config['paths']['repositories'],
[$this, 'checkRepositoryRules']
);

// 4. Audit Entities
$this->auditLayer(
'Entities',
$this->config['paths']['entities'],
[$this, 'checkEntityRules']
);

// 5. Audit Views
$this->auditViews();

$this->printSummary();
}

private function auditLayer(string $layerName, string $path, callable $ruleCallback): void
{
echo "{$this->config['colors']['bold']}Auditing {$layerName}...{$this->config['colors']['reset']}\n";

$files = glob($path . '/*.php');

foreach ($files as $file) {
$this->filesChecked++;
$content = file_get_contents($file);
$className = $this->getClassNameFromFile($content);
$tokens = token_get_all($content);

if (!$className) {
continue;
}

try {
$reflection = new ReflectionClass($className);
$errors = $ruleCallback($reflection, $content, $tokens);
} catch (Throwable $e) {
$errors = ["Reflection Error: " . $e->getMessage()];
}

if (!empty($errors)) {
foreach ($errors as $err) {
$this->violations[] = [
'layer' => $layerName,
'file' => $reflection->getShortName(),
'error' => $err
];
echo " {$this->config['colors']['red']}✘ {$reflection->getShortName()}: {$err}{$this->config['colors']['reset']}\n";
}
}
}
echo "\n";
}

private function checkControllerRules(ReflectionClass $ref, string $content, array $tokens): array
{
$errors = [];
$shortName = $ref->getShortName();

// A. Dependency Injection Check
$constructor = $ref->getConstructor();
if ($constructor) {
foreach ($constructor->getParameters() as $param) {
$type = $param->getType();
if ($type && !$type->isBuiltin()) {
$typeName = $type->getName();
if (str_contains($typeName, 'Repository')) {
$errors[] = "Strict MVC Violation: Injecting Repository '{$typeName}' directly. Use a Service.";
}
}
}
}

// B. Raw SQL Check
if ($this->containsRawSql($content)) {
$errors[] = "Potential Raw SQL detected (SELECT/INSERT/UPDATE).";
}

// C. Direct Output Check
// Whitelist BaseController as it provides the core response methods (render/jsonResponse)
$allowedEcho = ['NotificationController', 'FileController', 'BaseController'];
if (!in_array($shortName, $allowedEcho)) {
if ($this->hasToken($tokens, [T_ECHO, T_PRINT])) {
$errors[] = "Direct output detected (echo/print). Use render() or jsonResponse().";
}
}

return $errors;
}

private function checkServiceRules(ReflectionClass $ref, string $content, array $tokens): array
{
$errors = [];

// A. Dependency Injection Check
$constructor = $ref->getConstructor();
if ($constructor) {
foreach ($constructor->getParameters() as $param) {
$type = $param->getType();
if ($type && !$type->isBuiltin()) {
$typeName = $type->getName();
if (str_contains($typeName, 'App\Core\Session')) {
$errors[] = "Service Purity Violation: Injects Session. Pass data as method arguments instead.";
}
if (str_contains($typeName, 'App\Controllers')) {
$errors[] = "Circular Dependency: Service cannot inject a Controller.";
}
}
}
}

// B. Content Checks
// Logger class is allowed to echo if configured for CLI
if ($ref->getShortName() !== 'Logger') {
if ($this->hasToken($tokens, [T_ECHO, T_PRINT])) {
$errors[] = "Service Layer Purity: Direct output detected.";
}
}

if (preg_match('/<[a-z][\s\S]*>/i', $content)) {
$errors[] = "Service Layer Purity: Potential HTML tags found.";
}

if (str_contains($content, 'header(') || str_contains($content, 'setcookie(')) {
$errors[] = "Service Layer Purity: HTTP headers/cookies found. Move to Controller.";
}

return $errors;
}

private function checkRepositoryRules(ReflectionClass $ref, string $content, array $tokens): array
{
$errors = [];

$constructor = $ref->getConstructor();
if ($constructor) {
foreach ($constructor->getParameters() as $param) {
$type = $param->getType();
if ($type && !$type->isBuiltin()) {
$typeName = $type->getName();
if (str_contains($typeName, 'Service') && !str_contains($typeName, 'Provider')) {
$errors[] = "Repository Violation: Injects a Service '{$typeName}'. Repositories should be leaf nodes.";
}
}
}
}

return $errors;
}

private function checkEntityRules(ReflectionClass $ref, string $content, array $tokens): array
{
$errors = [];

if (!$ref->isReadOnly()) {
$errors[] = "Entity Violation: Class must be declared 'readonly'.";
}

return $errors;
}

private function auditViews(): void
{
echo "{$this->config['colors']['bold']}Auditing Views...{$this->config['colors']['reset']}\n";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->config['paths']['views']));

foreach ($iterator as $file) {
if ($file->isDir() || $file->getExtension() !== 'php') continue;

$this->filesChecked++;
$content = file_get_contents($file->getRealPath());
$tokens = token_get_all($content);
$relativePath = str_replace($this->config['paths']['views'], '', $file->getRealPath());

$errors = [];

if (str_contains($content, 'Database::getInstance') || str_contains($content, 'PDO')) {
$errors[] = "View Purity: Direct Database access detected.";
}

if ($this->hasToken($tokens, [T_NEW])) {
if (!str_contains($content, 'new DateTime')) {
$errors[] = "View Purity: Object instantiation detected (new ...). Use Presenters.";
}
}

if ($this->containsRawSql($content)) {
$errors[] = "View Purity: Raw SQL detected.";
}

if (!empty($errors)) {
foreach ($errors as $err) {
$this->violations[] = [
'layer' => 'Views',
'file' => $relativePath,
'error' => $err
];
echo " {$this->config['colors']['red']}✘ {$relativePath}: {$err}{$this->config['colors']['reset']}\n";
}
}
}
echo "\n";
}

// --- Helpers ---

private function getClassNameFromFile(string $content): ?string
{
$namespace = null;
$class = null;

// Strict namespace matching
if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
$namespace = trim($matches[1]);
}

// Robust class detection handling optional readonly/abstract/final modifiers
// Matches "class Name" ensuring it's not a variable or string content
if (preg_match('/^(?:\s*(?:abstract|final|readonly)\s+)*class\s+(\w+)/m', $content, $matches)) {
$class = trim($matches[1]);
}

return ($namespace && $class) ? $namespace . '\\' . $class : null;
}

private function hasToken(array $tokens, array $forbiddenTypes): bool
{
foreach ($tokens as $token) {
if (is_array($token) && in_array($token[0], $forbiddenTypes)) {
return true;
}
}
return false;
}

private function containsRawSql(string $content): bool
{
$patterns = [
'/SELECT\s+\*\s+FROM/i',
'/INSERT\s+INTO\s+/i',
'/UPDATE\s+\w+\s+SET/i',
'/DELETE\s+FROM\s+/i'
];

foreach ($patterns as $pattern) {
if (preg_match($pattern, $content)) {
return true;
}
}
return false;
}

private function printHeader(): void
{
echo "\n" . str_repeat("=", 60) . "\n";
echo " STARLIGHT DOMINION V2 - ARCHITECTURAL COMPLIANCE AUDIT\n";
echo str_repeat("=", 60) . "\n\n";
}

private function printSummary(): void
{
echo str_repeat("-", 60) . "\n";
echo "Files Checked: {$this->filesChecked}\n";
echo "Violations: " . count($this->violations) . "\n\n";

if (count($this->violations) === 0) {
echo "{$this->config['colors']['green']}{$this->config['colors']['bold']}✅ ARCHITECTURE VALIDATED: STRICT COMPLIANCE ACHIEVED{$this->config['colors']['reset']}\n";
exit(0);
} else {
echo "{$this->config['colors']['red']}{$this->config['colors']['bold']}❌ VALIDATION FAILED{$this->config['colors']['reset']}\n";
echo "Review the errors above and refactor the violations.\n";
exit(1);
}
}
}

$auditor = new ArchitectureAuditor($config);
$auditor->run();
