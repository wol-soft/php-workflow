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
    private float $startAt;
    private string $workflowName;

    public function setWorkflowName(string $workflowName): void
    {
        $this->workflowName = $workflowName;
    }

    public function addStep(int $stage, string $step, string $state, ?string $reason): void {
        $stage = $this->mapStage($stage);

        $this->stages[$stage][] = new Step($step, $state, $reason, $this->stepInfo);
        $this->stepInfo = [];
    }

    public function __toString(): string
    {
        $debug = "Process log for workflow '{$this->workflowName}':\n";

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

    public function startExecution(): void
    {
        $this->startAt = microtime(true);
    }

    public function stopExecution(): void
    {
        $this->attachStepInfo("Execution time: " . number_format(1000 * (microtime(true) - $this->startAt), 5) . 'ms');
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
}
