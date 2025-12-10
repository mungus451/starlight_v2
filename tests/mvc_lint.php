<?php
/**
 * mvc_lint.php (Smart Compliance Edition)
 *
 * Advanced heuristic MVC-S rule checker for PHP projects.
 *
 * IMPROVEMENTS:
 *   - Token-aware cleaning: Ignores HTML content and Comments for logic checks.
 *   - Smart SQL detection: Ignores the word "Select" in HTML forms.
 *   - Whitelisting: Allows specific Controllers (e.g., FileController) to output data.
 *   - Namespace support: Correctly identifies "new \DateTime" vs "new User".
 *
 * USAGE:
 *   php tests/mvc_lint.php              # Defaults to project root
 */

if (php_sapi_name() !== 'cli') {
    die("Error: This script must be run from the command line.\n");
}

// -----------------------------------------------------------------------------
// 1. CONFIGURATION
// -----------------------------------------------------------------------------

$layerConfig = [
    'controller' => [
        'pathContains'   => ['/Controllers/', '/app/Controllers/'],
        'fileNameRegex'  => '/Controller\.php$/i',
    ],
    'service' => [
        'pathContains'   => ['/Services/', '/app/Models/Services/'],
        'fileNameRegex'  => '/Service\.php$/i',
    ],
    'repository' => [
        'pathContains'   => ['/Repositories/', '/app/Models/Repositories/'],
        'fileNameRegex'  => '/Repository\.php$/i',
    ],
    'entity' => [
        'pathContains'   => ['/Entities/', '/app/Models/Entities/'],
        'fileNameRegex'  => '/\.php$/i',
    ],
    'presenter' => [
        'pathContains'   => ['/Presenters/', '/app/Presenters/'],
        'fileNameRegex'  => '/Presenter\.php$/i',
    ],
    'view' => [
        'pathContains'   => ['/views/', '/Views/'],
        'fileNameRegex'  => '/\.php$/i',
    ],
];

// Directories to skip
$ignoreDirs = [
    'vendor', 'node_modules', '.git', 'storage', 'cache', 'logs', 'tmp', 'tests', 'config', 'public'
];

// -----------------------------------------------------------------------------
// 2. RULESETS
// -----------------------------------------------------------------------------

$rules = [
    'controller' => [
        'forbiddenFunctions' => [
            'mysqli_query' => 'Controllers must not execute raw SQL.',
            'var_dump'     => 'Debug code detected.',
            'dd'           => 'Debug code detected.',
        ],
        // Allow specific files to output data (e.g., File downloads, JSON APIs)
        'exceptions' => [
            'FileController.php' => ['echo', 'header', 'readfile'],
            'NotificationController.php' => ['echo', 'json_encode'], 
            'BaseController.php' => ['echo', 'header', 'http_response_code']
        ],
        'forbiddenPatterns' => [
            'rawSql' => [
                // stricter SQL regex requiring structure (UPDATE x SET, DELETE FROM, etc)
                'pattern' => '/\b(SELECT\s+.+?\s+FROM|INSERT\s+INTO\s+|UPDATE\s+\w+\s+SET|DELETE\s+FROM)\s+/is',
                'message' => 'Raw SQL detected. Move database logic to a Repository.',
            ],
            'repoInjection' => [
                'pattern' => '/private\s+[a-zA-Z0-9_]*Repository\s+\$/',
                'message' => 'Direct Repository injection detected. Controllers must use Services.',
            ],
            // Only flag `echo` if not in exception list
            'directOutput' => [
                'pattern' => '/\b(echo|print)\b/i',
                'message' => 'Controllers should not echo output directly. Use render() or jsonResponse().',
                'tokenCheck' => true // Use token parser, not regex
            ]
        ],
    ],

    'service' => [
        'forbiddenFunctions' => [
            'echo'      => 'Services must not output data directly.',
            'print'     => 'Services must not output data directly.',
            'header'    => 'Services must not manage HTTP headers.',
            'setcookie' => 'Services must not manage cookies.',
            'exit'      => 'Services should throw Exceptions, not exit.',
            'die'       => 'Services should throw Exceptions, not die.',
        ],
        'forbiddenPatterns' => [
            'superglobals' => [
                'pattern' => '/(\$_GET|\$_POST|\$_SESSION|\$_COOKIE|\$_FILES)/',
                'message' => 'Direct access to Superglobals. Pass contextual data as arguments.',
            ],
            'htmlTags' => [
                'pattern' => '/<\s*(div|span|br|p|b|form|input|button)\b/i',
                'message' => 'HTML markup detected. Services should return raw data/DTOs.',
            ],
            'controllerDep' => [
                'pattern' => '/use\s+App\\\Controllers\\\/i',
                'message' => 'Circular Dependency: Service cannot depend on a Controller.',
            ],
        ],
    ],

    'repository' => [
        'forbiddenFunctions' => [
            'echo'   => 'Repositories must not output data.',
            'header' => 'Repositories must not manage HTTP headers.',
        ],
        'forbiddenPatterns' => [
            'serviceDep' => [
                'pattern' => '/use\s+App\\\Models\\\Services\\\/i',
                'message' => 'Architectural Violation: Repository cannot depend on a Service.',
            ],
        ],
    ],

    'entity' => [
        'forbiddenFunctions' => [
            'echo' => 'Entities must not output data.',
            'save' => 'Active Record violation: Entities should be pure DTOs, not save themselves.',
        ],
        'forbiddenPatterns' => [
            'dbAccess' => [
                'pattern' => '/(PDO|Database::|Repository)/i',
                'message' => 'Entities must not access the Database layer.',
            ],
        ],
        'specialChecks' => ['readonly'],
    ],

    'view' => [
        'forbiddenFunctions' => [
            'curl_exec'      => 'Views must not perform HTTP requests.',
            'mysqli_query'   => 'Views must not query the database.',
            'file_put_contents' => 'Views must not write to the filesystem.',
        ],
        'forbiddenPatterns' => [
            'rawSql' => [
                'pattern' => '/\b(SELECT\s+.+?\s+FROM|INSERT\s+INTO\s+|UPDATE\s+\w+\s+SET|DELETE\s+FROM)\s+/is',
                'message' => 'Raw SQL in View.',
            ],
            'dbAccess' => [
                'pattern' => '/(Database\s*::\s*getInstance|new\s+PDO\b|PDO\s*\()/i',
                'message' => 'Views must not access the database directly.',
            ],
            'objCreation' => [
                // Allow DateTime, DateInterval, Exception, standard PHP classes, and Presenters
                'pattern' => '/new\s+(?:\\\\)?(?!(?:DateTime|DateInterval|IntlDateFormatter|Exception|stdClass|.*Presenter))\w+/i',
                'message' => 'Object instantiation in View. Use a Presenter to prepare objects.',
            ],
        ],
    ],
];

// -----------------------------------------------------------------------------
// 3. ENGINE
// -----------------------------------------------------------------------------

function main(array $argv, array $layerConfig, array $ignoreDirs, array $rules): void {
    $scanRoot = $argv[1] ?? (__DIR__ . '/../');
    $root = realpath($scanRoot);

    if ($root === false || !is_dir($root)) {
        die("Error: Invalid directory '{$scanRoot}'\n");
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "   STARLIGHT DOMINION: MVC-S COMPLIANCE LINTER\n";
    echo "   Scanning: {$root}\n";
    echo str_repeat("=", 60) . "\n\n";

    $violations = [];
    $stats = ['files' => 0, 'lines' => 0];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;

        $path = $file->getPathname();
        if (shouldIgnoreDir($path, $ignoreDirs, $root)) continue;

        $code = file_get_contents($path);
        if (strpos($code, 'mvc-lint:disable-file') !== false) continue;

        $stats['files']++;
        $stats['lines'] += substr_count($code, "\n") + 1;

        $layer = classifyLayer($path, $layerConfig, $root);
        
        if ($layer === 'other') continue;

        $layerRules = $rules[$layer] ?? [];
        $fileName = basename($path);

        // 1. Prepare "Clean" Code (PHP Only, No Comments, No HTML)
        // This fixes the "Select" in HTML forms issue
        $phpOnlyCode = stripHtmlAndComments($code);

        // 2. Forbidden Functions (Token-aware on full code, checking context)
        if (!empty($layerRules['forbiddenFunctions'])) {
            $violations = array_merge($violations, checkFunctions($code, $path, $layer, $layerRules['forbiddenFunctions'], $layerRules['exceptions'] ?? [], $fileName));
        }

        // 3. Forbidden Patterns (Regex on Clean PHP Code)
        if (!empty($layerRules['forbiddenPatterns'])) {
            $violations = array_merge($violations, checkPatterns($phpOnlyCode, $path, $layer, $layerRules['forbiddenPatterns'], $layerRules['exceptions'] ?? [], $fileName));
        }

        // 4. Special Checks
        if (isset($layerRules['specialChecks']) && in_array('readonly', $layerRules['specialChecks'])) {
            $violations = array_merge($violations, checkEntityReadonly($code, $path));
        }
    }

    printReport($violations, $stats);
}

// -----------------------------------------------------------------------------
// 4. HELPERS
// -----------------------------------------------------------------------------

function shouldIgnoreDir(string $path, array $ignore, string $root): bool {
    $rel = str_replace($root, '', $path);
    $rel = str_replace('\\', '/', $rel);
    foreach ($ignore as $dir) {
        if (strpos($rel, '/' . $dir . '/') !== false) return true;
    }
    return false;
}

function classifyLayer(string $path, array $config, string $root): string {
    $normalized = str_replace('\\', '/', $path);
    foreach ($config as $layer => $rules) {
        foreach ($rules['pathContains'] as $segment) {
            if (strpos($normalized, $segment) !== false) {
                if (!empty($rules['fileNameRegex']) && !preg_match($rules['fileNameRegex'], $normalized)) {
                    continue;
                }
                return $layer;
            }
        }
    }
    return 'other';
}

/**
 * Removes HTML (T_INLINE_HTML) and Comments to prevent false positives in Regex.
 * PRESERVES NEWLINES to keep line numbers accurate in reports.
 */
function stripHtmlAndComments(string $code): string {
    $tokens = token_get_all($code);
    $output = '';
    
    foreach ($tokens as $token) {
        if (is_string($token)) {
            $output .= $token;
            continue;
        }

        [$id, $text] = $token;

        if ($id === T_INLINE_HTML || $id === T_COMMENT || $id === T_DOC_COMMENT) {
            // Replace content with newlines equivalent to what was removed
            $output .= str_repeat("\n", substr_count($text, "\n"));
        } else {
            $output .= $text;
        }
    }
    return $output;
}

function checkFunctions(string $code, string $file, string $layer, array $forbiddenMap, array $exceptions, string $fileName): array {
    // Check if this file is exempted from specific rules
    $fileExceptions = $exceptions[$fileName] ?? [];

    $tokens = token_get_all($code);
    $violations = [];
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token)) continue;

        [$id, $text, $line] = $token;
        $lowerText = strtolower($text);

        // Map token IDs
        $check = false;
        if ($id === T_STRING) $check = true;
        if ($id === T_ECHO) { $lowerText = 'echo'; $check = true; }
        if ($id === T_PRINT) { $lowerText = 'print'; $check = true; }
        if ($id === T_EXIT) { $lowerText = 'exit'; $check = true; }

        if ($check && isset($forbiddenMap[$lowerText])) {
            
            // Check whitelist exception
            if (in_array($lowerText, $fileExceptions)) continue;

            // Ensure it's a function call or language construct
            $isCall = true;
            
            // Look backward to ignore function definitions (function echo...) or static access (Class::echo)
            // or object access ($obj->echo)
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && ($tokens[$prev][0] === T_WHITESPACE)) $prev--;
            
            if ($prev >= 0) {
                $prevTok = $tokens[$prev];
                $prevCode = is_array($prevTok) ? $prevTok[0] : null;
                $prevStr = is_string($prevTok) ? $prevTok : null;

                if ($prevCode === T_FUNCTION || $prevCode === T_OBJECT_OPERATOR || $prevCode === T_DOUBLE_COLON || $prevCode === T_NEW) {
                    $isCall = false;
                }
            }

            if ($isCall) {
                $violations[] = [
                    'file' => $file,
                    'line' => $line,
                    'layer' => $layer,
                    'message' => $forbiddenMap[$lowerText] . " Found: '{$text}'"
                ];
            }
        }
    }
    return $violations;
}

function checkPatterns(string $cleanCode, string $file, string $layer, array $patterns, array $exceptions, string $fileName): array {
    $violations = [];
    $fileExceptions = $exceptions[$fileName] ?? [];

    foreach ($patterns as $key => $rule) {
        // If this specific rule key (e.g., 'directOutput') is handled via token check or excepted, skip
        if (in_array($key, $fileExceptions)) continue;
        
        // Skip rules marked for tokenCheck (handled by checkFunctions)
        if (!empty($rule['tokenCheck'])) continue;

        if (preg_match_all($rule['pattern'], $cleanCode, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                [$str, $offset] = $match;
                
                // Calculate line number based on clean code (which preserves newlines)
                $line = substr_count(substr($cleanCode, 0, $offset), "\n") + 1;
                
                $violations[] = [
                    'file' => $file,
                    'line' => $line,
                    'layer' => $layer,
                    'message' => $rule['message']
                ];
            }
        }
    }
    return $violations;
}

function checkEntityReadonly(string $code, string $file): array {
    $tokens = token_get_all($code);
    $violations = [];
    $count = count($tokens);
    $readonlyId = defined('T_READONLY') ? T_READONLY : -1;

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token)) continue;

        if ($token[0] === T_CLASS) {
            $next = $i + 1;
            while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) $next++;
            
            // If named class
            if (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_STRING) {
                $prev = $i - 1;
                $foundReadonly = false;
                while ($prev >= 0) {
                    $pt = $tokens[$prev];
                    if (is_string($pt)) break; // Punctuation stop
                    if ($pt[0] === $readonlyId) {
                        $foundReadonly = true;
                        break;
                    }
                    if (!in_array($pt[0], [T_WHITESPACE, T_FINAL, T_ABSTRACT, T_DOC_COMMENT, T_COMMENT])) break;
                    $prev--;
                }

                if (!$foundReadonly) {
                    $violations[] = [
                        'file' => $file,
                        'line' => $token[2],
                        'layer' => 'entity',
                        'message' => 'Entities must be declared as "readonly class".'
                    ];
                }
            }
        }
    }
    return $violations;
}

function printReport(array $violations, array $stats): void {
    echo "Checked {$stats['files']} files ({$stats['lines']} LOC).\n";

    if (empty($violations)) {
        echo "\033[32m✅ SUCCESS: Strict MVC Architecture Validated.\033[0m\n";
        exit(0);
    }

    echo "\033[31m❌ FOUND " . count($violations) . " VIOLATIONS:\033[0m\n\n";
    
    // Sort by file then line
    usort($violations, function($a, $b) {
        $c = strcmp($a['file'], $b['file']);
        return $c === 0 ? $a['line'] <=> $b['line'] : $c;
    });

    $lastFile = '';
    foreach ($violations as $v) {
        if ($v['file'] !== $lastFile) {
            echo "\033[1;33m" . basename($v['file']) . "\033[0m (" . dirname($v['file']) . ")\n";
            $lastFile = $v['file'];
        }
        echo "   L{$v['line']} [{$v['layer']}]: {$v['message']}\n";
    }
    
    echo "\n\033[31mFAILURE.\033[0m\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// 5. RUN
// -----------------------------------------------------------------------------

main($argv, $layerConfig, $ignoreDirs, $rules);