<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\Next\AllowNextAfter;
use PHPWorkflow\Step\Next\AllowNextOnSuccess;
use PHPWorkflow\Step\Next\AllowNextExecuteWorkflow;

class OnError extends Step
{
    use
        AllowNextOnSuccess,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    /** @var callable[] */
    private array $onError = [];

    public function onError(callable $onError): self
    {
        $this->onError[] = $onError;
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_ON_ERROR);

        foreach ($this->onError as $onError) {
            $this->wrapStepExecution($onError, $workflowState);
        }

        $this->next($workflowState);
    }
}
