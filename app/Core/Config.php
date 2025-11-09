<?php

namespace App\Core;

/**
 * A simple "dot-notation" config file loader.
 * It loads files from the /config/ directory.
 */
class Config
{
    private array $config = [];
    private string $configPath;

    public function __construct()
    {
        // Set the absolute path to the /config directory
        $this->configPath = __DIR__ . '/../../config/';
    }

    /**
     * Gets a configuration value using dot notation.
     * The first part of the key is the filename.
     *
     * Example: get('game_balance.training.soldiers')
     *
     * @param string $key The dot-notation key
     * @param mixed $default A default value to return if the key isn't found
     * @return mixed The config value or the default
     */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts); // e.g., 'game_balance'

        // Load the config file from /config/ if it hasn't been loaded yet
        if (!isset($this->config[$file])) {
            $path = $this->configPath . $file . '.php';

            if (!file_exists($path)) {
                error_log("Config file not found: " . $path);
                return $default;
            }
            
            $this->config[$file] = require $path;
        }

        // Traverse the loaded array to find the nested value
        $value = $this->config[$file];
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                // Key not found, return the default
                return $default;
            }
        }

        return $value;
    }
}