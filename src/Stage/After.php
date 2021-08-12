<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;

class After extends Stage
{
    use AllowNextExecuteWorkflow;

    private array $after = [];

    public function after(string $description, callable $after): self
    {
        $this->after[] = [$description, $after];
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_AFTER);

        foreach ($this->after as [$description, $after]) {
            $this->wrapStepExecution($description, $after, $workflowState);
        }

        $this->next($workflowState);
    }
}
