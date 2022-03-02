<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

use Exception;
use PHPWorkflow\State\ExecutionLog\Describable;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\WorkflowControl;

class WorkflowState
{
    public const STAGE_PREPARE = 0;
    public const STAGE_VALIDATE = 1;
    public const STAGE_BEFORE = 2;
    public const STAGE_PROCESS = 3;
    public const STAGE_ON_ERROR = 4;
    public const STAGE_ON_SUCCESS = 5;
    public const STAGE_AFTER = 6;
    public const STAGE_SUMMARY = 7;

    private ?Exception $processException = null;

    private string $workflowName;
    private int $stage = self::STAGE_PREPARE;
    private int $inLoop = 0;

    private WorkflowControl $workflowControl;
    private WorkflowContainer $workflowContainer;
    private ExecutionLog $executionLog;

    private array $middlewares = [];

    private static array $runningWorkflows = [];
    private WorkflowStep $currentStep;

    public function __construct(WorkflowContainer $workflowContainer)
    {
        $this->executionLog = new ExecutionLog($this);
        $this->workflowControl = new WorkflowControl($this);
        $this->workflowContainer = $workflowContainer;

        self::$runningWorkflows[] = $this;
    }

    public function close(bool $success, ?Exception $exception = null): WorkflowResult
    {
        array_pop(self::$runningWorkflows);

        return new WorkflowResult($this, $success, $exception);
    }

    public static function getRunningWorkflow(): ?self
    {
        return self::$runningWorkflows ? end(self::$runningWorkflows) : null;
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

    public function getWorkflowContainer(): WorkflowContainer
    {
        return $this->workflowContainer;
    }

    public function addExecutionLog(
        Describable $step,
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

    public function isInLoop(): bool
    {
        return $this->inLoop > 0;
    }

    public function setInLoop(bool $inLoop): void
    {
        $this->inLoop += $inLoop ? 1 : -1;
    }

    public function setStep(WorkflowStep $step): void
    {
        $this->currentStep = $step;
    }

    /**
     * @return WorkflowStep
     */
    public function getCurrentStep(): WorkflowStep
    {
        return $this->currentStep;
    }
}
