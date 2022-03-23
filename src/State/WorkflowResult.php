<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use Exception;
use PHPWorkflow\State\ExecutionLog\OutputFormat\OutputFormat;
use PHPWorkflow\State\ExecutionLog\OutputFormat\StringLog;
use PHPWorkflow\Step\WorkflowStep;

class WorkflowResult
{
    private bool $success;
    private ?Exception $exception;
    private WorkflowState $workflowState;

    public function __construct(
        WorkflowState $workflowState,
        bool $success,
        ?Exception $exception = null
    ) {
        $this->success = $success;
        $this->exception = $exception;
        $this->workflowState = $workflowState;
    }

    /**
     * Get the name of the executed workflow
     */
    public function getWorkflowName(): string
    {
        return $this->workflowState->getWorkflowName();
    }

    /**
     * Check if the workflow has been executed successfully
     */
    public function success(): bool
    {
        return $this->success;
    }

    /**
     * Get the full debug log for the workflow execution
     */
    public function debug(?OutputFormat $formatter = null)
    {
        return $this->workflowState->getExecutionLog()->debug($formatter ?? new StringLog());
    }

    /**
     * Check if the workflow execution has triggered warnings
     */
    public function hasWarnings(): bool
    {
        return count($this->workflowState->getExecutionLog()->getWarnings()) > 0;
    }

    /**
     * Get all warnings of the workflow execution.
     * Returns a nested array with all warnings grouped by stage (WorkflowState::STAGE_* constants).
     *
     * @return string[][]
     */
    public function getWarnings(): array
    {
        return $this->workflowState->getExecutionLog()->getWarnings();
    }

    /**
     * Returns the exception which lead to a failed workflow.
     * If the workflow was executed successfully null will be returned.
     */
    public function getException(): ?Exception
    {
        return $this->exception;
    }

    /**
     * Get the container of the process
     */
    public function getContainer(): WorkflowContainer
    {
        return $this->workflowState->getWorkflowContainer();
    }

    /**
     * Get the last executed step of the workflow
     */
    public function getLastStep(): WorkflowStep
    {
        return $this->workflowState->getCurrentStep();
    }
}
