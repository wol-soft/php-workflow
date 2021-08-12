<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\Next\AllowNextExecuteWorkflow;

class After extends Step
{
    use AllowNextExecuteWorkflow;

    /** @var callable[] */
    private array $after = [];

    public function after(callable $after): self
    {
        $this->after[] = $after;
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_AFTER);

        foreach ($this->after as $after) {
            $this->wrapStepExecution($after, $workflowState);
        }

        $this->next($workflowState);
    }
}
