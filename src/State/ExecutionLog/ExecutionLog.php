<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

use PHPWorkflow\State\WorkflowState;

class ExecutionLog
{
    const STATE_SUCCESS = 'ok';
    const STATE_SKIPPED = 'skipped';
    const STATE_FAILED = 'failed';

    /** @var Step[][] */
    private array $stages = [];
    /** @var string[] Collect additional debug info concerning the current step */
    private array $stepInfo = [];
    /** @var string[][] Collect all warnings which occurred during the workflow execution */
    private array $warnings = [];
    private int $warningsDuringStep = 0;

    private float $startAt;

    private WorkflowState $workflowState;

    public function __construct(WorkflowState $workflowState)
    {
        $this->workflowState = $workflowState;
    }

    public function addStep(int $stage, string $step, string $state, ?string $reason): void {
        $stage = $this->mapStage($stage);

        $this->stages[$stage][] = new Step($step, $state, $reason, $this->stepInfo, $this->warningsDuringStep);
        $this->stepInfo = [];
        $this->warningsDuringStep = 0;
    }

    public function __toString(): string
    {
        $debug = "Process log for workflow '{$this->workflowState->getWorkflowName()}':\n";

        foreach ($this->stages as $stage => $steps) {
            $debug .= "$stage:\n";

            foreach ($steps as $step) {
                $debug .= '  - ' . $step . "\n";
            }
        }

        return trim($debug);
    }

    public function attachStepInfo(string $info): void
    {
        $this->stepInfo[] = $info;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[$this->mapStage($this->workflowState->getStage())][] = $message;
        $this->warningsDuringStep++;
    }

    public function startExecution(): void
    {
        $this->startAt = microtime(true);
    }

    public function stopExecution(): void
    {
        $this->attachStepInfo("Execution time: " . number_format(1000 * (microtime(true) - $this->startAt), 5) . 'ms');

        if ($this->warnings) {
            $warnings = sprintf(
                "Got %s warning%s during the execution:\n        ",
                $amount = count($this->warnings, COUNT_RECURSIVE) - count($this->warnings),
                $amount > 1 ? 's' : '',
            );

            foreach ($this->warnings as $stage => $stageWarnings) {
                $warnings .= implode(
                    "\n        ",
                    array_map(
                        fn (string $warning): string => sprintf("%s: %s", $stage, $warning),
                        $stageWarnings,
                    ),
                );
            }

            $this->attachStepInfo($warnings);
        }
    }

    private function mapStage(int $stage): string
    {
        switch ($stage) {
            case WorkflowState::STAGE_PREPARE: return 'Prepare';
            case WorkflowState::STAGE_VALIDATION: return 'Validation';
            case WorkflowState::STAGE_BEFORE: return 'Before';
            case WorkflowState::STAGE_PROCESS: return 'Process';
            case WorkflowState::STAGE_ON_ERROR: return 'On Error';
            case WorkflowState::STAGE_ON_SUCCESS: return 'On Success';
            case WorkflowState::STAGE_AFTER: return 'After';
            case WorkflowState::STAGE_SUMMARY: return "\nSummary";
        }
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
