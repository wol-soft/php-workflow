<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\WorkflowStep;

abstract class MultiStepStage extends Stage
{
    protected const STAGE = 0;

    /** @var WorkflowStep[] */
    private array $steps = [];

    protected function addStep(WorkflowStep $step): self
    {
        $this->steps[] = $step;
        return $this;
    }

    protected function runStage(WorkflowState $workflowState): ?Stage
    {
        $workflowState->setStage(static::STAGE);

        foreach ($this->steps as $step) {
            $workflowState->setStep($step);
            $this->wrapStepExecution($step, $workflowState);
        }

        return $this->nextStage;
    }
}
