<?php

declare(strict_types=1);

namespace PHPWorkflow;

class Validator
{
    private string $description;
    private $validator;
    private bool $hardValidator;

    public function __construct(string $description, callable $validator, bool $hardValidator)
    {
        $this->description = $description;
        $this->validator = $validator;
        $this->hardValidator = $hardValidator;
    }

    public function getValidator(): callable
    {
        return $this->validator;
    }

    public function isHardValidator(): bool
    {
        return $this->hardValidator;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
