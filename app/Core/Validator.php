<?php

namespace App\Core;

/**
 * Robust Input Validator & Sanitizer.
 * 
 * Usage:
 * $val = $validator->make($_POST, ['email' => 'required|email']);
 * if ($val->fails()) { ... }
 * $cleanData = $val->validated();
 */
class Validator
{
    private array $source = [];
    private array $rules = [];
    private array $errors = [];
    private array $validatedData = [];

    /**
     * Factory method to create a new validation instance.
     * 
     * @param array $data Input data (e.g. $_POST)
     * @param array $rules Rules map (e.g. ['field' => 'required|int'])
     * @return self New instance with state
     */
    public function make(array $data, array $rules): self
    {
        $instance = new self();
        $instance->source = $data;
        $instance->rules = $rules;
        $instance->validate();
        return $instance;
    }

    /**
     * Returns true if validation passed.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns true if validation failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all error messages.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get the safe, sanitized, and type-cast data.
     * ONLY returns fields specified in the rules array.
     */
    public function validated(): array
    {
        return $this->validatedData;
    }

    /**
     * Internal validation runner.
     */
    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $ruleSet = explode('|', $ruleString);
            $rawValue = $this->source[$field] ?? null;
            
            // Check if field is present/required
            $isNullable = in_array('nullable', $ruleSet);
            $isRequired = in_array('required', $ruleSet);
            
            // Handle empty values
            if ($rawValue === null || $rawValue === '') {
                if ($isRequired) {
                    $this->addError($field, $this->formatLabel($field) . ' is required.');
                } elseif ($isNullable) {
                    $this->validatedData[$field] = null;
                }
                continue; // Skip further rules if empty
            }

            // Process Rules
            foreach ($ruleSet as $rule) {
                // Parse parameters (e.g., min:5)
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $method = 'check' . ucfirst($ruleName);
                
                if (method_exists($this, $method)) {
                    // If validation fails, stop processing this field
                    if (!$this->$method($field, $rawValue, $params)) {
                        break; 
                    }
                }
            }

            // If no errors for this field, sanitize and store
            if (!isset($this->errors[$field])) {
                $this->sanitizeAndStore($field, $rawValue, $ruleSet);
            }
        }
    }

    // --- Validation Checks (Return false on failure) ---

    private function checkRequired($field, $value, $params): bool
    {
        // Handled in main loop, but kept for completeness if needed logic changes
        return true;
    }

    private function checkNullable($field, $value, $params): bool
    {
        return true; 
    }

    private function checkEmail($field, $value, $params): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email address format.');
            return false;
        }
        return true;
    }

    private function checkInt($field, $value, $params): bool
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $this->formatLabel($field) . ' must be an integer.');
            return false;
        }
        return true;
    }

    private function checkFloat($field, $value, $params): bool
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            $this->addError($field, $this->formatLabel($field) . ' must be a number.');
            return false;
        }
        return true;
    }

    private function checkNumeric($field, $value, $params): bool
    {
        if (!is_numeric($value)) {
            $this->addError($field, $this->formatLabel($field) . ' must be numeric.');
            return false;
        }
        return true;
    }

    private function checkAlpha($field, $value, $params): bool
    {
        if (!ctype_alpha((string)$value)) {
            $this->addError($field, $this->formatLabel($field) . ' must contain only letters.');
            return false;
        }
        return true;
    }

    private function checkAlphanumeric($field, $value, $params): bool
    {
        if (!ctype_alnum((string)$value)) {
            $this->addError($field, $this->formatLabel($field) . ' must be alphanumeric.');
            return false;
        }
        return true;
    }

    private function checkMin($field, $value, $params): bool
    {
        $min = (int)($params[0] ?? 0);
        if (is_numeric($value)) {
            if ($value < $min) {
                $this->addError($field, $this->formatLabel($field) . " must be at least {$min}.");
                return false;
            }
        } else {
            if (mb_strlen((string)$value) < $min) {
                $this->addError($field, $this->formatLabel($field) . " must be at least {$min} characters.");
                return false;
            }
        }
        return true;
    }

    private function checkMax($field, $value, $params): bool
    {
        $max = (int)($params[0] ?? 255);
        if (is_numeric($value)) {
            if ($value > $max) {
                $this->addError($field, $this->formatLabel($field) . " cannot exceed {$max}.");
                return false;
            }
        } else {
            if (mb_strlen((string)$value) > $max) {
                $this->addError($field, $this->formatLabel($field) . " cannot exceed {$max} characters.");
                return false;
            }
        }
        return true;
    }

    private function checkMatch($field, $value, $params): bool
    {
        $targetField = $params[0] ?? '';
        $targetValue = $this->source[$targetField] ?? null;

        if ($value !== $targetValue) {
            $this->addError($field, $this->formatLabel($field) . ' does not match ' . $this->formatLabel($targetField) . '.');
            return false;
        }
        return true;
    }

    private function checkIn($field, $value, $params): bool
    {
        if (!in_array((string)$value, $params)) {
            $this->addError($field, $this->formatLabel($field) . ' contains an invalid selection.');
            return false;
        }
        return true;
    }

    private function checkUrl($field, $value, $params): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'Invalid URL format.');
            return false;
        }
        return true;
    }

    // --- Sanitization & Extraction ---

    private function sanitizeAndStore(string $field, $value, array $ruleSet): void
    {
        // Automatic Type Casting based on rules
        if (in_array('int', $ruleSet)) {
            $this->validatedData[$field] = (int)$value;
        } 
        elseif (in_array('float', $ruleSet) || in_array('numeric', $ruleSet)) {
            $this->validatedData[$field] = (float)$value;
        } 
        elseif (in_array('bool', $ruleSet)) {
            $this->validatedData[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } 
        elseif (in_array('email', $ruleSet)) {
            $this->validatedData[$field] = filter_var($value, FILTER_SANITIZE_EMAIL);
        }
        else {
            // Default: String Sanitization (Prevent XSS)
            // We use htmlspecialchars to prevent script injection in DB or View.
            // ENT_QUOTES handles both single and double quotes.
            $this->validatedData[$field] = htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
        }
    }

    // --- Utilities ---

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    private function formatLabel(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }
}