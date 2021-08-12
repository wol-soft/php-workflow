<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\ControlException;
use PHPWorkflow\Exception\WorkflowControl\FailStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Workflow;

abstract class Step
{
    protected ?Step $next = null;
    protected Workflow $workflow;

    public function __construct(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    abstract protected function run(WorkflowState $workflowState): void;

    protected function next(WorkflowState $workflowState): void
    {
        if ($this->next) {
            $this->next->run($workflowState);
        } else {
            if ($workflowState->getProcessException()) {
                throw $workflowState->getProcessException();
            }
        }
    }

    protected function wrapStepExecution(callable $stepExecution, WorkflowState $workflowState): void
    {
        try {
            ($stepExecution)($workflowState->getWorkflowControl());
        } catch (SkipStepException | FailStepException $exception) {
            // TODO: debug log

            if ($exception instanceof FailStepException && $workflowState->getStage() <= WorkflowState::STAGE_PROCESS) {
                throw $exception;
            }

            return;
        } catch (Exception $exception) {
            // bubble up control exceptions
            if ($exception instanceof ControlException) {
                throw $exception;
            }

            // TODO: debug log
        }
    }
}
