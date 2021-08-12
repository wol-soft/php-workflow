<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\Next\AllowNextAfter;
use PHPWorkflow\Step\Next\AllowNextOnError;
use PHPWorkflow\Step\Next\AllowNextExecuteWorkflow;

class OnSuccess extends Step
{
    use
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    /** @var callable[] */
    private array $onSuccess = [];

    public function onSuccess(callable $onSuccess): self
    {
        $this->onSuccess[] = $onSuccess;
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_ON_SUCCESS);

        foreach ($this->onSuccess as $onSuccess) {
            $this->wrapStepExecution($onSuccess, $workflowState);
        }

        $this->next($workflowState);
    }
}
