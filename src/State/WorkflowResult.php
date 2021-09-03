<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use Exception;

class WorkflowResult
{
    private bool $success;
    private WorkflowState $workflowState;
    private ?Exception $exception;
    private string $workflowName;

    public function __construct(
        string $workflowName,
        bool $success,
        bool $logErrors,
        WorkflowState $workflowState,
        ?Exception $exception = null
    ) {
        $this->workflowName = $workflowName;
        $this->success = $success;
        $this->workflowState = $workflowState;
        $this->exception = $exception;

        if ($logErrors) {
            if ($this->hasWarnings()) {
                trigger_error(
                    "Workflow {$workflowState->getWorkflowName()} finished with warnings. Check the workflow debug log for more details.",
                    E_USER_WARNING,
                );
            }

            // don't add failures from validations to the error log
            if (!$success && $workflowState->getStage() !== WorkflowState::STAGE_VALIDATION) {
                trigger_error(
                    "Workflow {$workflowState->getWorkflowName()} failed. Check the workflow debug log for more details.",
                    E_USER_WARNING,
                );
            }
        }
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function debug(): string
    {
        return (string) $this->workflowState->getExecutionLog();
    }

    public function hasWarnings(): bool
    {
        return count($this->workflowState->getExecutionLog()->getWarnings()) > 0;
    }

    public function getWarnings(): array
    {
        return $this->workflowState->getExecutionLog()->getWarnings();
    }

    public function getException(): ?Exception
    {
        return $this->exception;
    }

    public function getContainer(): WorkflowContainer
    {
        return $this->workflowState->getWorkflowContainer();
    }
}
