<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextProcess;

class Before extends Stage
{
    use AllowNextProcess;

    private array $before = [];

    public function before(string $description, callable $before): self
    {
        $this->before[] = [$description, $before];
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_BEFORE);

        foreach ($this->before as [$description, $before]) {
            $this->wrapStepExecution($description, $before, $workflowState);
        }

        $this->next($workflowState);
    }
}
