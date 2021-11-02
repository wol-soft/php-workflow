<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\WorkflowControl;

class Loop implements WorkflowStep
{
    use StepExecutionTrait;

    protected array $steps = [];
    protected LoopControl $loopControl;

    public function __construct(LoopControl $loopControl)
    {
        $this->loopControl = $loopControl;
    }

    public function addStep(WorkflowStep $step): self
    {
        $this->steps[] = $step;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->loopControl->getDescription();
    }

    public function run(WorkflowControl $control, WorkflowContainer $container)
    {
        $iteration = 0;

        while ($this->loopControl->executeNextIteration($iteration, $control, $container)) {
            foreach ($this->steps as $step) {
                $this->wrapStepExecution($step, WorkflowState::getRunningWorkflow());
            }

            WorkflowState::getRunningWorkflow()->addExecutionLog(sprintf("Loop iteration #%s", ++$iteration));
        }

        $control->attachStepInfo(sprintf("Loop finished after %s iterations", $iteration));
    }
}
