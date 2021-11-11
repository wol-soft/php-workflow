<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\BreakException;
use PHPWorkflow\Exception\WorkflowControl\ContinueException;
use PHPWorkflow\Exception\WorkflowControl\FailWorkflowException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\WorkflowControl;

class Loop implements WorkflowStep
{
    use StepExecutionTrait;

    protected array $steps = [];
    protected LoopControl $loopControl;
    private bool $continueOnError;

    public function __construct(LoopControl $loopControl, bool $continueOnError = false)
    {
        $this->loopControl = $loopControl;
        $this->continueOnError = $continueOnError;
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

        WorkflowState::getRunningWorkflow()->setInLoop(true);
        while (true) {
            $loopState = ExecutionLog::STATE_SUCCESS;
            $reason = null;

            try {
                if (!$this->loopControl->executeNextIteration($iteration, $control, $container)) {
                    break;
                }

                foreach ($this->steps as $step) {
                    $this->wrapStepExecution($step, WorkflowState::getRunningWorkflow());
                }

                $iteration++;
            } catch (Exception $exception) {
                $iteration++;
                $reason = $exception->getMessage();

                if ($exception instanceof ContinueException) {
                    $loopState = ExecutionLog::STATE_SKIPPED;
                } else {
                    if ($exception instanceof BreakException) {
                        WorkflowState::getRunningWorkflow()->addExecutionLog(
                            "Loop iteration #$iteration",
                            ExecutionLog::STATE_SKIPPED,
                            $reason,
                        );

                        $control->attachStepInfo("Loop break in iteration #$iteration");

                        break;
                    }

                    if (!$this->continueOnError ||
                        $exception instanceof SkipWorkflowException ||
                        $exception instanceof FailWorkflowException
                    ) {
                        throw $exception;
                    }

                    $control->warning("Loop iteration #$iteration failed. Continued execution.");
                    $loopState = ExecutionLog::STATE_FAILED;
                }
            }

            WorkflowState::getRunningWorkflow()->addExecutionLog("Loop iteration #$iteration", $loopState, $reason);
        }
        WorkflowState::getRunningWorkflow()->setInLoop(false);

        $control->attachStepInfo("Loop finished after $iteration iteration" . ($iteration === 1 ? '' : 's'));
    }
}
