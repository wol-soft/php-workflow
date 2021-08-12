<?php

declare(strict_types=1);

namespace PHPWorkflow;

use PHPWorkflow\Exception\WorkflowControl\FailStepException;
use PHPWorkflow\Exception\WorkflowControl\FailWorkflowException;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;

class WorkflowControl
{
    private ExecutionLog $executionLog;

    public function __construct(ExecutionLog $executionLog)
    {
        $this->executionLog = $executionLog;
    }

    public function skipStep(string $reason): void
    {
        throw new SkipStepException($reason);
    }

    public function failStep(string $reason): void
    {
        throw new FailStepException($reason);
    }

    public function failWorkflow(string $reason): void
    {
        throw new FailWorkflowException($reason);
    }

    public function skipWorkflow(string $reason): void
    {
        throw new SkipWorkflowException($reason);
    }

    public function attachStepInfo(string $info): void
    {
        $this->executionLog->attachStepInfo($info);
    }
}
