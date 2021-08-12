<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\Next\AllowNextAfter;
use PHPWorkflow\Step\Next\AllowNextOnError;
use PHPWorkflow\Step\Next\AllowNextOnSuccess;
use PHPWorkflow\Step\Next\AllowNextExecuteWorkflow;

class AfterProcess extends Step
{
    use
        AllowNextOnSuccess,
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    protected function run(WorkflowState $workflowState): void
    {
        $this->next($workflowState);
    }
}
