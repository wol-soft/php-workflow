<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextAfter;
use PHPWorkflow\Stage\Next\AllowNextOnError;
use PHPWorkflow\Stage\Next\AllowNextOnSuccess;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;

class AfterProcess extends Stage
{
    use
        AllowNextOnSuccess,
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    protected function runStage(WorkflowState $workflowState): ?Stage
    {
        return $this->nextStage;
    }
}
