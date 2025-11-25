<?php
/**
 * mvc_lint.php
 *
 * Simple heuristic MVC rule checker for PHP projects.
 *
 * USAGE:
 *   php mvc_lint.php /path/to/your/app
 *   php mvc_lint.php          # defaults to current directory
 *
 * WHAT IT DOES:
 *   - Recursively scans all *.php files (excluding common vendor/cache dirs)
 *   - Tries to classify each file as: model / view / controller / other
 *       -> Based on directory names and filename suffixes (configurable)
 *   - Applies per-layer rules to detect "MVC violations", e.g.:
 *       * Views that talk directly to DB (mysqli, PDO, raw SQL)
 *       * Controllers that contain raw SQL or full HTML templates
 *       * Models that output HTML or send headers/cookies
 *   - Prints a human-readable report of all violations.
 *
 * NOTE:
 *   - This is static analysis + regex heuristics — it will produce false
 *     positives/negatives. Adjust the config arrays to match your app’s
 *     structure and coding standards.
 */

// -----------------------------------------------------------------------------
// CONFIG: directory classification (adjust to your app structure)
// -----------------------------------------------------------------------------

/**
 * How we decide which "layer" a file belongs to.
 * You can tweak/add patterns to match your own directories and naming.
 */
$layerClassificationConfig = [
    'model' => [
        'pathContains'   => ['/Model/', '/Models/', '/app/Models/', '/src/Domain/'],
        'fileNameRegex'  => '/Model\.php$/i',
    ],
    'view' => [
        'pathContains'   => ['/View/', '/Views/', '/resources/views/', '/templates/'],
        'fileNameRegex'  => '/(View|\.view)\.php$/i',
    ],
    'controller' => [
        'pathContains'   => ['/Controller/', '/Controllers/', '/app/Controllers/'],
        'fileNameRegex'  => '/Controller\.php$/i',
    ],
    // Files that don’t match anything above fall back to "other"
];

/**
 * Directories to skip entirely while scanning.
 */
$ignoreDirectories = [
    'vendor',
    'node_modules',
    '.git',
    'storage',
    'cache',
    'logs',
    'tmp',
];

// -----------------------------------------------------------------------------
// CONFIG: MVC rules per layer (edit these to your standards)
// -----------------------------------------------------------------------------

/**
 * Each layer has:
 *   - forbiddenFunctions: [ functionName => message ]
 *   - forbiddenPatterns:  [ ruleId => [ 'pattern' => regex, 'message' => string ] ]
 */
$mvcRules = [
    'model' => [
        'forbiddenFunctions' => [
            // Presentation / HTTP concerns should not live in models:
            'view'      => 'Models must not render views directly (calling view()).',
            'header'    => 'Models must not send HTTP headers; keep them in controllers or middleware.',
            'setcookie' => 'Models must not manipulate cookies; keep this at the edge (controller/middleware).',
            'echo'      => 'Models should avoid direct output; return data to controllers instead.',
            'print'     => 'Models should avoid direct output; return data to controllers instead.',
        ],
        'forbiddenPatterns'  => [
            'htmlMarkup' => [
                // Basic HTML tags: suggests presentation logic leaking into model.
                'pattern' => '/<\s*(html|head|body|div|span|p|h[1-6]|table|form)\b/i',
                'message' => 'Models should not contain HTML markup; move rendering to a view/template.',
            ],
        ],
    ],

    'view' => [
        'forbiddenFunctions' => [
            // Data access in views:
            'mysqli_query'   => 'Views must not talk to the database directly (mysqli_query). Move logic to a model.',
            'mysqli_prepare' => 'Views must not talk to the database directly (mysqli_prepare).',
            'mysqli_connect' => 'Views must not create DB connections (mysqli_connect).',
            'PDO'            => 'Views must not instantiate PDO directly; use models/repositories.',
            'curl_exec'      => 'Views should not perform HTTP requests; keep I/O in models/services.',
        ],
        'forbiddenPatterns'  => [
            'rawSql' => [
                'pattern' => '/\b(SELECT|INSERT\s+INTO|UPDATE\s+\w+|DELETE\s+FROM)\b/i',
                'message' => 'Views must not contain raw SQL; shift data access to a model or repository.',
            ],
            // This is very heuristic; can be noisy. Comment out if too strict.
            'heavyLogic' => [
                'pattern' => '/\b(if|foreach|for|while|switch)\b.+\$/i',
                'message' => 'Views should avoid heavy business logic; keep them mostly presentational.',
            ],
        ],
    ],

    'controller' => [
        'forbiddenFunctions' => [
            // Controllers shouldn’t be running raw SQL either in a pure MVC setup:
            'mysqli_query'   => 'Controllers should not execute raw queries; delegate to models or repositories.',
            'mysqli_prepare' => 'Controllers should not execute raw queries; delegate to models or repositories.',
            'mysqli_connect' => 'Controllers should not create low-level DB connections; use a model/service.',
            'PDO'            => 'Controllers should not instantiate PDO directly; use a model/service.',
        ],
        'forbiddenPatterns'  => [
            'rawSql' => [
                'pattern' => '/\b(SELECT|INSERT\s+INTO|UPDATE\s+\w+|DELETE\s+FROM)\b/i',
                'message' => 'Controllers should not contain raw SQL; move it into models.',
            ],
            'fullHtmlDoc' => [
                'pattern' => '/<\s*(html|head|body)\b/i',
                'message' => 'Controllers should not render full HTML documents; use views/templates.',
            ],
        ],
    ],

    // "other" – you can define general rules if you want:
    'other' => [
        'forbiddenFunctions' => [],
        'forbiddenPatterns'  => [],
    ],
];

// -----------------------------------------------------------------------------
// Implementation
// -----------------------------------------------------------------------------

/**
 * Entry point.
 */
function main(
    array $argv,
    array $layerClassificationConfig,
    array $ignoreDirectories,
    array $mvcRules
): void {
    $root = $argv[1] ?? getcwd();
    $root = rtrim($root, DIRECTORY_SEPARATOR);

    if (!is_dir($root)) {
        fwrite(STDERR, "Error: '{$root}' is not a directory.\n");
        exit(1);
    }

    echo "MVC Lint: scanning directory: {$root}\n\n";

    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
        )
    );

    foreach ($iterator as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        if (!$fileInfo->isFile()) {
            continue;
        }

        $filePath = $fileInfo->getPathname();

        if (!isPhpFile($filePath)) {
            continue;
        }

        if (isInIgnoredDir($filePath, $ignoreDirectories, $root)) {
            continue;
        }

        $layer = classifyLayer($filePath, $layerClassificationConfig, $root);
        $fileViolations = analyzeFile($filePath, $layer, $mvcRules);

        $violations = array_merge($violations, $fileViolations);
    }

    printReport($violations);
}

/**
 * Determine if file is a PHP file.
 */
function isPhpFile(string $path): bool
{
    return (bool)preg_match('/\.php$/i', $path);
}

/**
 * Check if a file is inside one of the ignored directories.
 */
function isInIgnoredDir(string $filePath, array $ignoreDirectories, string $root): bool
{
    $normalized = str_replace('\\', '/', $filePath);
    $rootNorm   = str_replace('\\', '/', $root);

    foreach ($ignoreDirectories as $dir) {
        $needle = $rootNorm . '/' . trim($dir, '/');
        if (strpos($normalized, $needle . '/') !== false || substr($normalized, -strlen($dir)) === $dir) {
            return true;
        }
    }

    return false;
}

/**
 * Classify the layer of a file based on path and filename.
 */
function classifyLayer(string $filePath, array $config, string $root): string
{
    $normalized = str_replace('\\', '/', $filePath);
    $relative   = ltrim(str_replace(str_replace('\\', '/', $root), '', $normalized), '/');
    $fileName   = basename($normalized);

    foreach ($config as $layer => $rules) {
        // Check pathContains
        if (!empty($rules['pathContains'])) {
            foreach ($rules['pathContains'] as $needle) {
                $needleNorm = str_replace('\\', '/', $needle);
                if (strpos($normalized, $needleNorm) !== false || strpos($relative, trim($needleNorm, '/')) !== false) {
                    return $layer;
                }
            }
        }

        // Check fileNameRegex
        if (!empty($rules['fileNameRegex']) && preg_match($rules['fileNameRegex'], $fileName)) {
            return $layer;
        }
    }

    return 'other';
}

/**
 * Analyze a single PHP file for MVC violations.
 */
function analyzeFile(string $filePath, string $layer, array $mvcRules): array
{
    $code = file_get_contents($filePath);
    if ($code === false) {
        return [];
    }

    $rules = $mvcRules[$layer] ?? $mvcRules['other'];

    $violations = [];

    // 1. Token-based forbidden function usage
    if (!empty($rules['forbiddenFunctions'])) {
        $violations = array_merge(
            $violations,
            findForbiddenFunctions($code, $filePath, $layer, $rules['forbiddenFunctions'])
        );
    }

    // 2. Regex-based forbidden patterns
    if (!empty($rules['forbiddenPatterns'])) {
        $violations = array_merge(
            $violations,
            findForbiddenPatterns($code, $filePath, $layer, $rules['forbiddenPatterns'])
        );
    }

    return $violations;
}

/**
 * Find forbidden function usage using tokens.
 */
function findForbiddenFunctions(string $code, string $filePath, string $layer, array $forbiddenFunctions): array
{
    $tokens = token_get_all($code);
    $violations = [];

    $forbiddenNames = array_change_key_case($forbiddenFunctions, CASE_LOWER);

    $tokenCount = count($tokens);

    for ($i = 0; $i < $tokenCount; $i++) {
        $token = $tokens[$i];

        if (!is_array($token)) {
            continue;
        }

        [$id, $text, $line] = $token;

        if ($id === T_STRING) {
            $nameLower = strtolower($text);

            if (!array_key_exists($nameLower, $forbiddenNames)) {
                continue;
            }

            // Look ahead for "(" to confirm it's used as a function/method call.
            $isFunctionCall = false;
            for ($j = $i + 1; $j < $tokenCount; $j++) {
                $next = $tokens[$j];

                // Skip whitespace & comments
                if (is_array($next) && in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                if ($next === '(') {
                    $isFunctionCall = true;
                }
                break;
            }

            if ($isFunctionCall) {
                $violations[] = [
                    'file'    => $filePath,
                    'line'    => $line,
                    'layer'   => $layer,
                    'rule'    => "function:{$nameLower}",
                    'snippet' => trimShortLine(getLineFromCode($code, $line)),
                    'message' => $forbiddenNames[$nameLower],
                ];
            }
        }
    }

    return $violations;
}

/**
 * Find forbidden regex patterns.
 */
function findForbiddenPatterns(string $code, string $filePath, string $layer, array $patterns): array
{
    $violations = [];

    foreach ($patterns as $ruleId => $rule) {
        $pattern = $rule['pattern'];
        $message = $rule['message'];

        if (@preg_match($pattern, '') === false) {
            // Invalid regex; skip
            continue;
        }

        if (!preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }

        foreach ($matches[0] as [$matchText, $offset]) {
            $line = lineNumberFromOffset($code, $offset);

            $violations[] = [
                'file'    => $filePath,
                'line'    => $line,
                'layer'   => $layer,
                'rule'    => "pattern:{$ruleId}",
                'snippet' => trimShortLine(getLineFromCode($code, $line)),
                'message' => $message,
            ];
        }
    }

    return $violations;
}

/**
 * Compute 1-based line number from string offset.
 */
function lineNumberFromOffset(string $code, int $offset): int
{
    if ($offset <= 0) {
        return 1;
    }
    $before = substr($code, 0, $offset);
    return substr_count($before, "\n") + 1;
}

/**
 * Get a specific line from the code (1-based).
 */
function getLineFromCode(string $code, int $lineNumber): string
{
    $lines = preg_split("/\r\n|\n|\r/", $code);
    if ($lineNumber < 1 || $lineNumber > count($lines)) {
        return '';
    }
    return $lines[$lineNumber - 1];
}

/**
 * Trim a line for display in the report.
 */
function trimShortLine(string $line, int $maxLen = 120): string
{
    $line = trim($line);
    if (strlen($line) <= $maxLen) {
        return $line;
    }
    return substr($line, 0, $maxLen - 3) . '...';
}

/**
 * Print the final report.
 */
function printReport(array $violations): void
{
    if (empty($violations)) {
        echo "No MVC rule violations found (according to current heuristics).\n";
        return;
    }

    usort($violations, function ($a, $b) {
        return [$a['file'], $a['line']] <=> [$b['file'], $b['line']];
    });

    echo "=== MVC Rule Violations ===\n\n";

    $currentFile = null;
    foreach ($violations as $v) {
        if ($v['file'] !== $currentFile) {
            $currentFile = $v['file'];
            echo $currentFile . "\n";
            echo str_repeat('-', strlen($currentFile)) . "\n";
        }

        $line    = $v['line'];
        $layer   = strtoupper($v['layer']);
        $rule    = $v['rule'];
        $message = $v['message'];
        $snippet = $v['snippet'];

        echo "[{$layer}] Line {$line} ({$rule})\n";
        echo "  {$message}\n";
        if ($snippet !== '') {
            echo "  > {$snippet}\n";
        }
        echo "\n";
    }

    $count = count($violations);
    echo "Total violations: {$count}\n";
}

// -----------------------------------------------------------------------------
// Run
// -----------------------------------------------------------------------------

main($argv, $layerClassificationConfig, $ignoreDirectories, $mvcRules);
