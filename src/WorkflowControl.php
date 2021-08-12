<?php

declare(strict_types=1);

namespace PHPWorkflow;

use PHPWorkflow\Exception\WorkflowControl\FailStepException;
use PHPWorkflow\Exception\WorkflowControl\FailWorkflowException;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;

class WorkflowControl
{
    public function skipStep(string $reason): void
    {
        throw new SkipStepException("Skipped step: " . $reason);
    }

    public function failStep(string $reason): void
    {
        throw new FailStepException("Step failed: " . $reason);
    }

    public function failWorkflow(string $reason): void
    {
        throw new FailWorkflowException("Workflow failed: " . $reason);
    }

    public function skipWorkflow(string $reason): void
    {
        throw new SkipWorkflowException("Workflow skipped: " . $reason);
    }
}
