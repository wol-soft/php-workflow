<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\ExecutionLog\Summary;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowResult;
use PHPWorkflow\State\WorkflowState;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(
        ?WorkflowContainer $workflowContainer = null,
        bool $throwOnFailure = true
    ): WorkflowResult {
        if (!$workflowContainer) {
            $workflowContainer = new WorkflowContainer();
        }

        $workflowContainer->set(
            '__internalExecutionConfiguration',
            [
                'throwOnFailure' => $throwOnFailure,
            ],
        );

        $workflowState = new WorkflowState($workflowContainer);

        try {
            $workflowState->getExecutionLog()->startExecution();

            $this->workflow->runStage($workflowState);

            $workflowState->getExecutionLog()->stopExecution();
            $workflowState->setStage(WorkflowState::STAGE_SUMMARY);
            $workflowState->addExecutionLog(new Summary('Workflow execution'));
        } catch (Exception $exception) {
            $workflowState->getExecutionLog()->stopExecution();
            $workflowState->setStage(WorkflowState::STAGE_SUMMARY);
            $workflowState->addExecutionLog(
                new Summary('Workflow execution'),
                $exception instanceof SkipWorkflowException ? ExecutionLog::STATE_SKIPPED : ExecutionLog::STATE_FAILED,
                $exception->getMessage(),
            );

            if ($exception instanceof SkipWorkflowException) {
                return $workflowState->close(true);
            }

            $result = $workflowState->close(false, $exception);

            if ($throwOnFailure) {
                throw new WorkflowException(
                    $result,
                    "Workflow '{$workflowState->getWorkflowName()}' failed",
                    $exception,
                );
            }

            return $result;
        }

        return $workflowState->close(true);
    }
}
