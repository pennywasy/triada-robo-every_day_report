<?php
namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return !empty($this->errors)
            ? reset($this->errors)[0]
            : null;
    }
}