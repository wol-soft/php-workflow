<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\FailStepException;
use PHPWorkflow\Exception\WorkflowControl\LoopControlException;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\Middleware\WorkflowStepDependencyCheck;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\WorkflowState;

trait StepExecutionTrait
{
    protected function wrapStepExecution(WorkflowStep $step, WorkflowState $workflowState): void {
        try {
            ($this->resolveMiddleware($step, $workflowState))();
        } catch (SkipStepException | FailStepException $exception) {
            $workflowState->addExecutionLog(
                $step,
                $exception instanceof FailStepException ? ExecutionLog::STATE_FAILED : ExecutionLog::STATE_SKIPPED,
                $exception->getMessage(),
            );

            if ($exception instanceof FailStepException) {
                // cancel the workflow during preparation
                if ($workflowState->getStage() <= WorkflowState::STAGE_PROCESS) {
                    throw $exception;
                }

                $workflowState->getExecutionLog()->addWarning(sprintf('Step failed (%s)', get_class($step)), true);
            }

            // bubble up the exception so the loop control can handle the exception
            if ($exception instanceof LoopControlException) {
                throw $exception;
            }

            return;
        } catch (Exception $exception) {
            $workflowState->addExecutionLog(
                $step,
                $exception instanceof SkipWorkflowException ? ExecutionLog::STATE_SKIPPED : ExecutionLog::STATE_FAILED,
                $exception->getMessage(),
            );

            // cancel the workflow during preparation
            if ($workflowState->getStage() <= WorkflowState::STAGE_PROCESS) {
                throw $exception;
            }

            if (!($exception instanceof SkipWorkflowException)) {
                $workflowState->getExecutionLog()->addWarning(sprintf('Step failed (%s)', get_class($step)), true);
            }

            return;
        }

        $workflowState->addExecutionLog($step);
    }

    private function resolveMiddleware(WorkflowStep $step, WorkflowState $workflowState): callable
    {
        $tip = fn () => $step->run($workflowState->getWorkflowControl(), $workflowState->getWorkflowContainer());

        $middlewares = $workflowState->getMiddlewares();

        if (PHP_MAJOR_VERSION >= 8) {
            array_unshift($middlewares, new WorkflowStepDependencyCheck());
        }

        foreach ($middlewares as $middleware) {
            $tip = fn () => $middleware(
                $tip,
                $workflowState->getWorkflowControl(),
                $workflowState->getWorkflowContainer(),
                $step,
            );
        }

        return $tip;
    }
}
