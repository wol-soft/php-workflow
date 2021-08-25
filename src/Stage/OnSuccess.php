<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\ExecutableWorkflow;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextAfter;
use PHPWorkflow\Stage\Next\AllowNextOnError;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;
use PHPWorkflow\Step\WorkflowStep;

class OnSuccess extends MultiStepStage implements ExecutableWorkflow
{
    use
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    const STAGE = WorkflowState::STAGE_ON_SUCCESS;

    public function onSuccess(WorkflowStep $step): self
    {
        return $this->addStep($step);
    }

    protected function runStage(WorkflowState $workflowState): ?Stage
    {
        // don't execute onSuccess steps if the workflow failed
        if ($workflowState->getProcessException()) {
            return $this->nextStage;
        }

        return parent::runStage($workflowState);
    }
}
