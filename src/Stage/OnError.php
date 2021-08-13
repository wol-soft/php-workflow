<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextAfter;
use PHPWorkflow\Stage\Next\AllowNextOnSuccess;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;
use PHPWorkflow\Step\WorkflowStep;

class OnError extends MultiStepStage
{
    use
        AllowNextOnSuccess,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    const STAGE = WorkflowState::STAGE_ON_ERROR;

    public function onError(WorkflowStep $step): self
    {
        return $this->addStep($step);
    }

    protected function run(WorkflowState $workflowState): ?Stage
    {
        // don't execute onError steps if the workflow was successful
        if (!$workflowState->getProcessException()) {
            return $this->next;
        }

        return parent::run($workflowState);
    }
}
