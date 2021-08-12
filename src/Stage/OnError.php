<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextAfter;
use PHPWorkflow\Stage\Next\AllowNextOnSuccess;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;

class OnError extends Stage
{
    use
        AllowNextOnSuccess,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    /** @var callable[] */
    private array $onError = [];

    public function onError(string $description, callable $onError): self
    {
        $this->onError[] = [$description, $onError];
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_ON_ERROR);

        foreach ($this->onError as [$description, $onError]) {
            $this->wrapStepExecution($description, $onError, $workflowState);
        }

        $this->next($workflowState);
    }
}
