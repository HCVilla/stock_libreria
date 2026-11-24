<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        if (trim((string) ($this->data[$field] ?? '')) === '') {
            $this->errors[$field] = 'El campo ' . $label . ' es obligatorio.';
        }

        return $this;
    }

    public function max(string $field, int $max, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));

        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field] = $label . ' no puede superar los ' . $max . ' caracteres.';
        }

        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->data[$field] ?? '';

        if ($value === '' || !is_numeric($value)) {
            $this->errors[$field] = $label . ' debe ser un número válido.';
        }

        return $this;
    }

    public function minValue(string $field, float $min, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        if ((float) ($this->data[$field] ?? 0) < $min) {
            $this->errors[$field] = $label . ' no puede ser menor que ' . $min . '.';
        }

        return $this;
    }

    public function integer(string $field, string $label, int $min = 1): self
    {
        $value = $this->data[$field] ?? '';

        if ($value === '' || filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = $label . ' es obligatorio.';

            return $this;
        }

        if ((int) $value < $min) {
            $this->errors[$field] = $label . ' no es válido.';
        }

        return $this;
    }

    /**
     * @param array<int, string> $allowed
     */
    public function in(string $field, array $allowed, string $label): self
    {
        if (!in_array((string) ($this->data[$field] ?? ''), $allowed, true)) {
            $this->errors[$field] = $label . ' no es válido.';
        }

        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] = $message;

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
