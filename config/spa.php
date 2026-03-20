<?php

declare(strict_types=1);

return [
    'notifications_enabled' => filter_var($_ENV['FEATURE_SPA_NOTIFICATIONS'] ?? false, FILTER_VALIDATE_BOOL),
    'glossary_enabled'      => filter_var($_ENV['FEATURE_SPA_GLOSSARY'] ?? false, FILTER_VALIDATE_BOOL),
    'leaderboard_enabled'   => filter_var($_ENV['FEATURE_SPA_LEADERBOARD'] ?? false, FILTER_VALIDATE_BOOL),
    'bank_enabled'          => filter_var($_ENV['FEATURE_SPA_BANK'] ?? false, FILTER_VALIDATE_BOOL),
    'training_enabled'      => filter_var($_ENV['FEATURE_SPA_TRAINING'] ?? false, FILTER_VALIDATE_BOOL),
    'profile_enabled'       => filter_var($_ENV['FEATURE_SPA_PROFILE'] ?? false, FILTER_VALIDATE_BOOL),
    'structures_enabled'    => filter_var($_ENV['FEATURE_SPA_STRUCTURES'] ?? false, FILTER_VALIDATE_BOOL),
];
