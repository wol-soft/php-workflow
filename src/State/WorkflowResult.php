<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use PHPWorkflow\State\ExecutionLog\ExecutionLog;

class WorkflowResult
{
    private bool $success;
    private ExecutionLog $executionLog;

    public function __construct(bool $success, ExecutionLog $executionLog)
    {
        $this->success = $success;
        $this->executionLog = $executionLog;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function debug(): string
    {
        return (string) $this->executionLog;
    }
}
