<?php

declare(strict_types=1);

namespace App\Support;

final class RequestValidator
{
    /**
     * Very small validator supporting required|email|string|min:x|max:x rules.
     * Replace with a robust library if needed.
     * @param array<string,mixed> $data
     * @param array<string,string> $rules
     * @return array<string,string> field => error
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            $rules = explode('|', $ruleStr);
            foreach ($rules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = 'required';
                    break;
                }
                if ($value === null) {
                    continue;
                }
                if ($rule === 'email' && !filter_var((string)$value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'email';
                    break;
                }
                if ($rule === 'string' && !is_string($value)) {
                    $errors[$field] = 'string';
                    break;
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int)substr($rule, 4);
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $errors[$field] = 'min';
                        break;
                    }
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int)substr($rule, 4);
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $errors[$field] = 'max';
                        break;
                    }
                }
            }
        }
        return $errors;
    }
}
