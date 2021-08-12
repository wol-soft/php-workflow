<?php

declare(strict_types=1);

namespace PHPWorkflow\Exception;

use Exception;
use PHPWorkflow\State\WorkflowResult;

class WorkflowException extends Exception
{
    private WorkflowResult $workflowResult;

    public function __construct(WorkflowResult $workflowResult, string $message, Exception $previous)
    {
        parent::__construct($message, 0, $previous);
        $this->workflowResult = $workflowResult;
    }

    public function getWorkflowResult(): WorkflowResult
    {
        return $this->workflowResult;
    }
}
