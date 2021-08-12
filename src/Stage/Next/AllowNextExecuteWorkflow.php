<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\WorkflowResult;
use PHPWorkflow\State\WorkflowState;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(bool $throwOnFailure = true): WorkflowResult
    {
        $workflowState = new WorkflowState();

        try {
            $this->workflow->run($workflowState);
        } catch (Exception $exception) {
            $workflowState->setStage(WorkflowState::STAGE_SUMMARY);
            $workflowState->addExecutionLog(
                'Workflow execution',
                $exception instanceof SkipWorkflowException ? ExecutionLog::STATE_SKIPPED : ExecutionLog::STATE_FAILED,
                $exception->getMessage(),
            );

            if ($exception instanceof SkipWorkflowException) {
                return new WorkflowResult(true, $workflowState->getExecutionLog());
            }

            $result = new WorkflowResult(true, $workflowState->getExecutionLog());

            if ($throwOnFailure) {
                throw new WorkflowException($result, "Workflow {$workflowState->getWorkflowName()} failed", $exception);
            }

            return $result;
        }

        $workflowState->setStage(WorkflowState::STAGE_SUMMARY);
        $workflowState->addExecutionLog('Workflow execution');

        return new WorkflowResult(true, $workflowState->getExecutionLog());
    }
}
