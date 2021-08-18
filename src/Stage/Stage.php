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
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\Workflow;

abstract class Stage
{
    protected ?Stage $nextStage = null;
    protected Workflow $workflow;

    public function __construct(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    abstract protected function runStage(WorkflowState $workflowState): ?Stage;

    protected function wrapStepExecution(WorkflowStep $step, WorkflowState $workflowState): void {
        try {
            ($this->resolveMiddleware($step, $workflowState))();
        } catch (SkipStepException | FailStepException $exception) {
            $workflowState->addExecutionLog(
                $step->getDescription(),
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
                $step->getDescription(),
                $exception instanceof SkipWorkflowException ? ExecutionLog::STATE_SUCCESS : ExecutionLog::STATE_FAILED,
                $exception->getMessage(),
            );

            // bubble up control exceptions or cancel the workflow during preparation
            if ($exception instanceof ControlException || $workflowState->getStage() <= WorkflowState::STAGE_PROCESS) {
                throw $exception;
            }
        }

        $workflowState->addExecutionLog($step->getDescription());
    }

    private function resolveMiddleware(WorkflowStep $step, WorkflowState $workflowState): callable
    {
        $tip = fn () => $step->run($workflowState->getWorkflowControl(), $workflowState->getWorkflowContainer());

        foreach ($workflowState->getMiddlewares() as $middleware) {
            $tip = fn () => $middleware(
                $tip,
                $workflowState->getWorkflowControl(),
                $workflowState->getWorkflowContainer(),
            );
        }

        return $tip;
    }
}
