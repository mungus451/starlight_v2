<?php

declare(strict_types=1);

return [
    'notifications_enabled' => filter_var($_ENV['FEATURE_SPA_NOTIFICATIONS'] ?? false, FILTER_VALIDATE_BOOL),
    'glossary_enabled'      => filter_var($_ENV['FEATURE_SPA_GLOSSARY'] ?? false, FILTER_VALIDATE_BOOL),
];
