<?php
declare(strict_types=1);

class Validator {
    public static function required(array $data, array $fields): array {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null) {
                $errors[] = "El campo '{$field}' es obligatorio.";
            }
        }
        return $errors;
    }

    public static function email(string $value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function intRange(int $value, int $min, int $max): bool {
        return $value >= $min && $value <= $max;
    }

    public static function floatRange(float $value, float $min, float $max): bool {
        return $value >= $min && $value <= $max;
    }

    public static function inEnum(string $value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }

    public static function maxLength(string $value, int $max): bool {
        return mb_strlen($value) <= $max;
    }

    public static function dateFormat(string $value, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    public static function sanitizeString(?string $value): string {
        if ($value === null) return '';
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    public static function validateInput(array $rules, array $data): array {
        $errors = [];
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $rulesList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            foreach ($rulesList as $rule) {
                $rule = trim($rule);
                if ($rule === 'required' && ($value === null || (is_string($value) && trim($value) === ''))) {
                    $errors[] = "El campo '{$field}' es obligatorio.";
                    break; // deja de validar este campo
                }
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    continue; // skip further rules if empty and not required
                }
                if ($rule === 'email' && !self::email((string) $value)) {
                    $errors[] = "El campo '{$field}' debe ser un correo válido.";
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (!self::maxLength((string) $value, $max)) {
                        $errors[] = "El campo '{$field}' no debe exceder {$max} caracteres.";
                    }
                }
                if (str_starts_with($rule, 'int')) {
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[] = "El campo '{$field}' debe ser un número entero.";
                    }
                }
                if (str_starts_with($rule, 'float')) {
                    if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                        $errors[] = "El campo '{$field}' debe ser un número decimal.";
                    }
                }
                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!self::inEnum((string) $value, $allowed)) {
                        $errors[] = "El campo '{$field}' no es válido.";
                    }
                }
                if ($rule === 'date' && !self::dateFormat((string) $value)) {
                    $errors[] = "El campo '{$field}' debe tener formato YYYY-MM-DD.";
                }
            }
        }
        return $errors;
    }
}
