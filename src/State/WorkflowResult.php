<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use Exception;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;

class WorkflowResult
{
    private bool $success;
    private ?Exception $exception;
    private string $workflowName;
    private ExecutionLog $executionLog;
    private WorkflowContainer $workflowContainer;

    public function __construct(
        WorkflowState $workflowState,
        bool $success,
        ?Exception $exception = null
    ) {
        $this->workflowName = $workflowState->getWorkflowName();
        $this->executionLog = $workflowState->getExecutionLog();
        $this->workflowContainer = $workflowState->getWorkflowContainer();

        $this->success = $success;
        $this->exception = $exception;
    }

    /**
     * Get the name of the executed workflow
     */
    public function getWorkflowName(): string
    {
        return $this->workflowName;
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
    public function debug(): string
    {
        return (string) $this->executionLog;
    }

    /**
     * Check if the workflow execution has triggered warnings
     */
    public function hasWarnings(): bool
    {
        return count($this->executionLog->getWarnings()) > 0;
    }

    /**
     * Get all warnings of the workflow execution.
     * Returns a nested array with all warnings grouped by stage.
     *
     * @return string[][]
     */
    public function getWarnings(): array
    {
        return $this->executionLog->getWarnings();
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
        return $this->workflowContainer;
    }
}
