<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\ControlException;
use PHPWorkflow\Exception\WorkflowControl\FailStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Workflow;

abstract class Stage
{
    protected ?Stage $next = null;
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

    protected function wrapStepExecution(
        string $description,
        callable $stepExecution,
        WorkflowState $workflowState
    ): void {
        try {
            ($stepExecution)($workflowState->getWorkflowControl());
        } catch (SkipStepException | FailStepException $exception) {
            $workflowState->addExecutionLog(
                $description,
                $exception instanceof FailStepException ? ExecutionLog::STATE_FAILED : ExecutionLog::STATE_SKIPPED,
                $exception->getMessage(),
            );

            // cancel the workflow during preparation
            if ($exception instanceof FailStepException && $workflowState->getStage() <= WorkflowState::STAGE_PROCESS) {
                throw $exception;
            }

            return;
        } catch (Exception $exception) {
            $workflowState->addExecutionLog(
                $description,
                $exception instanceof SkipWorkflowException ? ExecutionLog::STATE_SKIPPED : ExecutionLog::STATE_FAILED,
                $exception->getMessage(),
            );

            // bubble up control exceptions or cancel the workflow during preparation
            if ($exception instanceof ControlException || $workflowState->getStage() <= WorkflowState::STAGE_PROCESS) {
                throw $exception;
            }
        }

        $workflowState->addExecutionLog($description);
    }
}
