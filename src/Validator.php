<?php

declare(strict_types=1);

namespace PHPWorkflow;

class Validator
{
    /**
     * @var callable
     */
    private $validator;
    private bool $hardValidator;

    public function __construct(callable $validator, bool $hardValidator)
    {
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
}
