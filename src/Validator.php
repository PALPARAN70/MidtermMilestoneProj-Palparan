<?php
declare(strict_types=1);

/**
 * Collects error messages per field. Once a field fails, later rules
 * for that same field are skipped so the member sees one clear message.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function required(string $field, string $value, string $label): self
    {
        if (!$this->has($field) && $value === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function length(string $field, string $value, int $min, int $max, string $label): self
    {
        $len = mb_strlen($value);
        if (!$this->has($field) && ($len < $min || $len > $max)) {
            $this->errors[$field] = "{$label} must be {$min} to {$max} characters.";
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if (!$this->has($field) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Enter a valid email address.';
        }
        return $this;
    }

    public function matches(string $field, string $pattern, string $value, string $message): self
    {
        if (!$this->has($field) && !preg_match($pattern, $value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function intBetween(string $field, mixed $value, int $min, int $max, string $label): self
    {
        $ok = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if (!$this->has($field) && $ok === false) {
            $this->errors[$field] = "{$label} must be a whole number from {$min} to {$max}.";
        }
        return $this;
    }

    public function same(string $field, string $a, string $b, string $message): self
    {
        if (!$this->has($field) && $a !== $b) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function add(string $field, string $message): self
    {
        if (!$this->has($field)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
