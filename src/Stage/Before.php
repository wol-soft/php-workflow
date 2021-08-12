<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextProcess;

class Before extends Stage
{
    use AllowNextProcess;

    /** @var callable[] */
    private array $before = [];

    public function before(callable $before): self
    {
        $this->before[] = $before;
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_BEFORE);

        foreach ($this->before as $before) {
            $this->wrapStepExecution($before, $workflowState);
        }

        $this->next($workflowState);
    }
}
