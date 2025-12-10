--- START OF FILE tests/mvc_lint.php ---

<?php
/**
 * mvc_lint.php (Robust Edition)
 *
 * Advanced heuristic MVC-S rule checker for PHP projects.
 * Enforces strict Separation of Concerns, Service Isolation, and View Purity.
 *
 * USAGE:
 *   php tests/mvc_lint.php /path/to/app
 *   php tests/mvc_lint.php          # defaults to project root
 */

// -----------------------------------------------------------------------------
// CONFIG: Classification Rules
// -----------------------------------------------------------------------------

$layerClassificationConfig = [
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
    'view' => [
        'pathContains'   => ['/views/', '/templates/'],
        'fileNameRegex'  => '/\.php$/i',
    ],
    'presenter' => [
        'pathContains'   => ['/Presenters/', '/app/Presenters/'],
        'fileNameRegex'  => '/Presenter\.php$/i',
    ],
];

$ignoreDirectories = [
    'vendor', 'node_modules', '.git', 'storage', 'cache', 'logs', 'tmp', 'tests', 'config'
];

// -----------------------------------------------------------------------------
// CONFIG: Rulesets
// -----------------------------------------------------------------------------

$globalForbiddenTokens = [
    'die'      => 'Do not use die() or exit(). Handle exceptions gracefully.',
    'exit'     => 'Do not use die() or exit(). Handle exceptions gracefully.',
    'var_dump' => 'Debug code detected (var_dump).',
    'print_r'  => 'Debug code detected (print_r).',
    'dd'       => 'Debug code detected (dd).',
];

$mvcRules = [
    // --- CONTROLLERS ---
    // Should handle Input/Output, but NOT Business Logic or SQL
    'controller' => [
        'forbiddenFunctions' => [
            'mysqli_query'   => 'Controllers must not execute raw SQL.',
            'pdo_query'      => 'Controllers must not execute raw SQL.',
            'new PDO'        => 'Controllers must not instantiate DB connections.',
        ],
        'forbiddenPatterns' => [
            'rawSql' => [
                'pattern' => '/\b(SELECT\s+\*|INSERT\s+INTO|UPDATE\s+\w+\s+SET|DELETE\s+FROM)\b/i',
                'message' => 'Raw SQL detected in Controller. Move to Repository.',
            ],
            'htmlTags' => [
                'pattern' => '/<\s*(div|span|table|ul|li|script)\b/i',
                'message' => 'HTML markup detected in Controller. Move to View.',
            ],
            'repoInjection' => [
                'pattern' => '/private\s+[a-zA-Z0-9]*Repository\s+\$/',
                'message' => 'Direct Repository injection in Controller. Use a Service.',
            ]
        ]
    ],

    // --- SERVICES ---
    // Pure Business Logic. No HTML, No HTTP Headers, No Superglobals.
    'service' => [
        'forbiddenFunctions' => [
            'echo'      => 'Services must not output data directly.',
            'print'     => 'Services must not output data directly.',
            'header'    => 'Services must not manage HTTP headers.',
            'setcookie' => 'Services must not manage cookies.',
            'http_response_code' => 'Services must not set HTTP codes.',
        ],
        'forbiddenPatterns' => [
            'superglobals' => [
                'pattern' => '/(\$_GET|\$_POST|\$_SESSION|\$_COOKIE|\$_FILES)/',
                'message' => 'Direct access to Superglobals in Service. Pass data as arguments.',
            ],
            'htmlTags' => [
                'pattern' => '/<\s*(div|span|br|p|b)\b/i',
                'message' => 'HTML markup detected in Service. Logic only.',
            ],
            'controllerDep' => [
                'pattern' => '/use\s+App\\\Controllers\\\/i',
                'message' => 'Circular Dependency: Service should not depend on Controller.',
            ]
        ]
    ],

    // --- REPOSITORIES ---
    // Database Access Only. No Business Logic, No View Logic.
    'repository' => [
        'forbiddenFunctions' => [
            'echo'   => 'Repositories must not output data.',
            'header' => 'Repositories must not manage HTTP headers.',
        ],
        'forbiddenPatterns' => [
            'serviceDep' => [
                'pattern' => '/use\s+App\\\Models\\\Services\\\/i',
                'message' => 'Repositories should not depend on Services (Leaf node violation).',
            ],
            'superglobals' => [
                'pattern' => '/(\$_GET|\$_POST|\$_SESSION)/',
                'message' => 'Repositories must not read Global State.',
            ],
            'htmlTags' => [
                'pattern' => '/<\s*[a-z]+\b/i',
                'message' => 'HTML detected in Repository.',
            ]
        ]
    ],

    // --- ENTITIES ---
    // Pure DTOs. Immutable.
    'entity' => [
        'forbiddenFunctions' => [
            'echo' => 'Entities must not output data.',
            'save' => 'Entities should not save themselves (Active Record violation).',
        ],
        'forbiddenPatterns' => [
            'notReadonly' => [
                'pattern' => '/^((?!readonly class).)*class\s+\w+/m',
                'message' => 'Entities must be defined as "readonly class".',
            ],
            'dbAccess' => [
                'pattern' => '/(PDO|Database::)/i',
                'message' => 'Entities must not access the Database.',
            ]
        ]
    ],

    // --- PRESENTERS ---
    // View Logic Only. No DB.
    'presenter' => [
        'forbiddenPatterns' => [
            'dbAccess' => [
                'pattern' => '/(PDO|Database::|Repository)/i',
                'message' => 'Presenters must not fetch data. They only format existing data.',
            ],
            'echo' => [
                'pattern' => '/\becho\b/',
                'message' => 'Presenters should return formatted data, not echo it.',
            ]
        ]
    ],

    // --- VIEWS ---
    // Presentation Only. No DB, No Complex Logic.
    'view' => [
        'forbiddenFunctions' => [
            'Database::getInstance' => 'Views must not access DB directly.',
            'new PDO' => 'Views must not access DB directly.',
            'curl_exec' => 'Views must not perform HTTP requests.',
        ],
        'forbiddenPatterns' => [
            'rawSql' => [
                'pattern' => '/\b(SELECT|INSERT|UPDATE|DELETE)\b/i',
                'message' => 'Raw SQL in View.',
            ],
            'objCreation' => [
                'pattern' => '/new\s+(?!DateTime|Exception)\w+/i',
                'message' => 'Object instantiation in View. Use Presenter.',
            ]
        ]
    ]
];

// -----------------------------------------------------------------------------
// Engine
// -----------------------------------------------------------------------------

function main(array $argv, array $config, array $ignore, array $rules): void {
    // Determine root directory (assuming script is in tests/)
    $root = realpath(__DIR__ . '/../');
    if (!is_dir($root)) {
        fwrite(STDERR, "Error: Cannot determine project root from " . __DIR__ . "\n");
        exit(1);
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "   MVC-S ROBUST ARCHITECTURE LINT\n";
    echo str_repeat("=", 60) . "\n";
    echo "Scanning: {$root}\n\n";

    $violations = [];
    $stats = ['files' => 0, 'lines' => 0];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;
        
        $path = $file->getPathname();
        if (isInIgnoredDir($path, $ignore, $root)) continue;

        $stats['files']++;
        $code = file_get_contents($path);
        $stats['lines'] += substr_count($code, "\n");

        $layer = classifyLayer($path, $config, $root);
        
        // 1. Global Token Check (die, var_dump)
        global $globalForbiddenTokens;
        $violations = array_merge($violations, checkTokens($code, $path, 'global', $globalForbiddenTokens));

        // 2. Layer Specific Checks
        if (isset($rules[$layer])) {
            $layerRules = $rules[$layer];
            
            // Check Forbidden Functions
            if (isset($layerRules['forbiddenFunctions'])) {
                $violations = array_merge($violations, checkTokens($code, $path, $layer, $layerRules['forbiddenFunctions']));
            }

            // Check Forbidden Patterns (Regex)
            if (isset($layerRules['forbiddenPatterns'])) {
                $violations = array_merge($violations, checkPatterns($code, $path, $layer, $layerRules['forbiddenPatterns']));
            }
        }
    }

    printReport($violations, $stats);
}

function isInIgnoredDir(string $path, array $ignore, string $root): bool {
    $rel = str_replace($root, '', $path);
    foreach ($ignore as $dir) {
        if (strpos($rel, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR) !== false) return true;
    }
    return false;
}

function classifyLayer(string $path, array $config, string $root): string {
    $normalized = str_replace('\\', '/', $path);
    foreach ($config as $layer => $rules) {
        // Check path containment
        foreach ($rules['pathContains'] as $segment) {
            if (strpos($normalized, $segment) !== false) {
                // Verify filename match if exists
                if (isset($rules['fileNameRegex'])) {
                    if (preg_match($rules['fileNameRegex'], $normalized)) return $layer;
                } else {
                    return $layer;
                }
            }
        }
    }
    return 'other';
}

function checkTokens(string $code, string $file, string $layer, array $forbiddenMap): array {
    $tokens = token_get_all($code);
    $violations = [];
    
    foreach ($tokens as $token) {
        if (!is_array($token)) continue;
        [$id, $text, $line] = $token;
        
        // Function calls usually appear as T_STRING
        if ($id === T_STRING || $id === T_EXIT || $id === T_ECHO || $id === T_PRINT) {
            $check = strtolower($text);
            if (array_key_exists($check, $forbiddenMap)) {
                // Simple heuristic: check if followed by '(' for functions
                // (Omitted for simplicity, assuming existence of token is enough for strict lint)
                $violations[] = [
                    'file' => $file,
                    'line' => $line,
                    'layer' => $layer,
                    'message' => $forbiddenMap[$check] . " Found '{$text}'"
                ];
            }
        }
    }
    return $violations;
}

function checkPatterns(string $code, string $file, string $layer, array $patterns): array {
    $violations = [];
    foreach ($patterns as $key => $rule) {
        if (preg_match_all($rule['pattern'], $code, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                [$text, $offset] = $match;
                $line = substr_count(substr($code, 0, $offset), "\n") + 1;
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

function printReport(array $violations, array $stats): void {
    echo "Scanned {$stats['files']} files ({$stats['lines']} LOC).\n";
    
    if (empty($violations)) {
        echo "\033[32m✅ SUCCESS: No Architecture Violations Found.\033[0m\n";
        exit(0);
    }

    echo "\033[31m❌ FOUND " . count($violations) . " VIOLATIONS:\033[0m\n\n";
    
    // Sort by file
    usort($violations, fn($a, $b) => strcmp($a['file'], $b['file']));

    $lastFile = '';
    foreach ($violations as $v) {
        if ($v['file'] !== $lastFile) {
            echo "\033[1;33m" . basename($v['file']) . "\033[0m (" . dirname($v['file']) . ")\n";
            $lastFile = $v['file'];
        }
        echo "   Line {$v['line']} [{$v['layer']}]: {$v['message']}\n";
    }
    
    echo "\n\033[31mFAILURE.\033[0m\n";
    exit(1);
}

// Run
main($argv, $layerClassificationConfig, $ignoreDirectories, $mvcRules);