<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\WorkflowControl;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(bool $throwOnFailure = true): bool
    {
        $workflowState = new WorkflowState(new WorkflowControl());

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
                return true;
            }

            if ($throwOnFailure) {
                throw $exception;
            }

            return false;
        }

        return true;
    }
}
