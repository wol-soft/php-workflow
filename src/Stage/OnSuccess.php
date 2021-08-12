<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextAfter;
use PHPWorkflow\Stage\Next\AllowNextOnError;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;

class OnSuccess extends Stage
{
    use
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    private array $onSuccess = [];

    public function onSuccess(string $description, callable $onSuccess): self
    {
        $this->onSuccess[] = [$description, $onSuccess];
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        // don't execute onSuccess steps if the workflow failed
        if ($workflowState->getProcessException()) {
            $this->next($workflowState);
        }

        $workflowState->setStage(WorkflowState::STAGE_ON_SUCCESS);

        foreach ($this->onSuccess as [$description, $onSuccess]) {
            $this->wrapStepExecution($description, $onSuccess, $workflowState);
        }

        $this->next($workflowState);
    }
}
