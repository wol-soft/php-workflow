<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use Exception;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\WorkflowControl;

class WorkflowState
{
    public const STAGE_PREPARE = 0;
    public const STAGE_VALIDATION = 1;
    public const STAGE_BEFORE = 2;
    public const STAGE_PROCESS = 3;
    public const STAGE_ON_ERROR = 4;
    public const STAGE_ON_SUCCESS = 5;
    public const STAGE_AFTER = 6;
    public const STAGE_SUMMARY = 7;

    private ?Exception $processException = null;
    private int $stage = self::STAGE_VALIDATION;
    private WorkflowControl $workflowControl;
    private WorkflowContainer $workflowContainer;

    private ExecutionLog $executionLog;
    private string $workflowName;
    private array $middlewares = [];

    public function __construct(WorkflowContainer $workflowContainer)
    {
        $this->executionLog = new ExecutionLog();
        $this->workflowControl = new WorkflowControl($this->executionLog);
        $this->workflowContainer = $workflowContainer;
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
        $this->executionLog->setWorkflowName($workflowName);
    }

    public function getWorkflowControl(): WorkflowControl
    {
        return $this->workflowControl;
    }

    public function getWorkflowContainer(): WorkflowContainer
    {
        return $this->workflowContainer;
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

    public function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
