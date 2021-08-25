<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\ExecutableWorkflow;
use PHPWorkflow\Stage\Next\AllowNextAfter;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;
use PHPWorkflow\Stage\Next\AllowNextOnError;
use PHPWorkflow\Stage\Next\AllowNextOnSuccess;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\WorkflowStep;

class Process extends MultiStepStage implements ExecutableWorkflow
{
    use
        AllowNextOnSuccess,
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    const STAGE = WorkflowState::STAGE_PROCESS;

    public function process(WorkflowStep $step): self
    {
        return $this->addStep($step);
    }

    protected function runStage(WorkflowState $workflowState): ?Stage
    {
        try {
            parent::runStage($workflowState);
        } catch (Exception $exception) {
            $workflowState->setProcessException($exception);
        }

        return $this->nextStage;
    }
}
