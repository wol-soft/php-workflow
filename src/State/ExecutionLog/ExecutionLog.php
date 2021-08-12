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

    public function addStep(int $stage, string $step, string $state, ?string $reason): void {
        $stage = $this->mapStage($stage);

        $this->stages[$stage][] = new Step($step, $state, $reason);
    }

    public function __toString(): string
    {
        $debug = '';

        foreach ($this->stages as $stage => $steps) {
            $debug .= "$stage:\n";

            foreach ($steps as $step) {
                $debug .= '  - ' . $step . "\n";
            }
        }

        return trim($debug);
    }

    private function mapStage(int $stage): string
    {
        switch ($stage) {
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
