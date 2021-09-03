<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowResult;
use PHPWorkflow\State\WorkflowState;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(
        WorkflowContainer $workflowContainer = null,
        bool $throwOnFailure = true,
        bool $logErrors = true
    ): WorkflowResult {
        if (!$workflowContainer) {
            $workflowContainer = new WorkflowContainer();
        }

        $workflowContainer->set(
            '__internalExecutionConfiguration',
            [
                'throwOnFailure' => $throwOnFailure,
                'logErrors' => $logErrors,
            ],
        );

        $workflowState = new WorkflowState($workflowContainer);

        try {
            $workflowState->getExecutionLog()->startExecution();

            $this->workflow->runStage($workflowState);

            $workflowState->getExecutionLog()->stopExecution();
            $workflowState->setStage(WorkflowState::STAGE_SUMMARY);
            $workflowState->addExecutionLog('Workflow execution');
        } catch (Exception $exception) {
            $workflowState->getExecutionLog()->stopExecution();
            $workflowState->setStage(WorkflowState::STAGE_SUMMARY);
            $workflowState->addExecutionLog(
                'Workflow execution',
                $exception instanceof SkipWorkflowException ? ExecutionLog::STATE_SKIPPED : ExecutionLog::STATE_FAILED,
                $exception->getMessage(),
            );

            if ($exception instanceof SkipWorkflowException) {
                return new WorkflowResult($workflowState->getWorkflowName(), true, $logErrors, $workflowState);
            }

            $result = new WorkflowResult(
                $workflowState->getWorkflowName(),
                false,
                $logErrors,
                $workflowState,
                $exception,
            );

            if ($throwOnFailure) {
                throw new WorkflowException($result, "Workflow {$workflowState->getWorkflowName()} failed", $exception);
            }

            return $result;
        }

        return new WorkflowResult($workflowState->getWorkflowName(), true, $logErrors, $workflowState);
    }
}
