<?php

namespace App\Core;

/**
 * A simple validation service.
 * Allows chaining rules like 'required|email|min:5'.
 */
class Validator
{
    private array $errors = [];

    /**
     * Validates an array of data against a set of rules.
     *
     * @param array $data The input data (e.g., $_POST)
     * @param array $rules An associative array of rules (e.g., ['email' => 'required|email'])
     * @return array An array of error messages. Empty if validation passed.
     */
    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleSet = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleSet as $rule) {
                // Parse parameters (e.g., min:5 -> rule=min, params=[5])
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule);
                    $params = explode(',', $paramStr);
                }

                $method = 'validate' . ucfirst($rule);
                
                if (method_exists($this, $method)) {
                    // If a field is optional and empty, skip other checks unless it's 'required'
                    if ($rule !== 'required' && ($value === null || $value === '')) {
                        continue;
                    }
                    
                    $this->$method($field, $value, $params, $data);
                }
            }
        }

        return $this->errors;
    }

    // --- Validation Rules ---

    private function validateRequired(string $field, $value, array $params, array $data): void
    {
        if ($value === null || trim((string)$value) === '') {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
        }
    }

    private function validateEmail(string $field, $value, array $params, array $data): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email address format.');
        }
    }

    private function validateMin(string $field, $value, array $params, array $data): void
    {
        $min = (int)($params[0] ?? 0);
        if (mb_strlen((string)$value) < $min) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.");
        }
    }

    private function validateMax(string $field, $value, array $params, array $data): void
    {
        $max = (int)($params[0] ?? 255);
        if (mb_strlen((string)$value) > $max) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.");
        }
    }

    private function validateNumeric(string $field, $value, array $params, array $data): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a number.');
        }
    }

    private function validateAlphanumeric(string $field, $value, array $params, array $data): void
    {
        if (!ctype_alnum((string)$value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must contain only letters and numbers.');
        }
    }

    /**
     * Checks if the field matches another field (e.g., confirm_password matches password).
     */
    private function validateMatch(string $field, $value, array $params, array $data): void
    {
        $targetField = $params[0] ?? '';
        $targetValue = $data[$targetField] ?? null;

        if ($value !== $targetValue) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' does not match.');
        }
    }

    // --- Helper ---

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}