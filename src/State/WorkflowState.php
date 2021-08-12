<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use Exception;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\WorkflowControl;

class WorkflowState
{
    public const STAGE_VALIDATION = 0;
    public const STAGE_BEFORE = 1;
    public const STAGE_PROCESS = 2;
    public const STAGE_ON_ERROR = 3;
    public const STAGE_ON_SUCCESS = 4;
    public const STAGE_AFTER = 5;
    public const STAGE_SUMMARY = 6;

    private ?Exception $processException = null;
    private int $stage = self::STAGE_VALIDATION;
    private WorkflowControl $workflowControl;

    private ExecutionLog $executionLog;
    private string $workflowName;

    public function __construct(WorkflowControl $workflowControl)
    {
        $this->workflowControl = $workflowControl;
        $this->executionLog = new ExecutionLog();
    }

    public function getProcessException(): ?Exception
    {
        return $this->processException;
    }

    public function setProcessException(?Exception $processException): void
    {
        $this->processException = $processException;
    }

    public function getStage(): int
    {
        return $this->stage;
    }

    public function setStage(int $stage): void
    {
        $this->stage = $stage;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function setWorkflowName(string $workflowName): void
    {
        $this->workflowName = $workflowName;
    }

    public function getWorkflowControl(): WorkflowControl
    {
        return $this->workflowControl;
    }

    public function addExecutionLog(
        string $step,
        string $state = ExecutionLog::STATE_SUCCESS,
        ?string $reason = null
    ): void {
        $this->executionLog->addStep($this->stage, $step, $state, $reason);
    }

    public function getExecutionLog(): ExecutionLog
    {
        return $this->executionLog;
    }
}
