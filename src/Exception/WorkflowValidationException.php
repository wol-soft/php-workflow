<?php

declare(strict_types=1);

namespace PHPWorkflow\Exception;

use Exception;

class WorkflowValidationException extends Exception
{
    /** @var Exception[] */
    private array $validationErrors;

    public function __construct(array $validationErrors)
    {
        parent::__construct();

        $this->validationErrors = $validationErrors;
    }

    /**
     * @return Exception[]
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
