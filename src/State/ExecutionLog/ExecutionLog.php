<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

use PHPWorkflow\State\ExecutionLog\OutputFormat\OutputFormat;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\WorkflowStep;

class ExecutionLog
{
    const STATE_SUCCESS = 'ok';
    const STATE_SKIPPED = 'skipped';
    const STATE_FAILED = 'failed';

    /** @var Step[][] */
    private array $stages = [];
    /** @var StepInfo[] Collect additional debug info concerning the current step */
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

    public function addStep(int $stage, Describable $step, string $state, ?string $reason): void {
        $this->stages[$stage][] = new Step($step, $state, $reason, $this->stepInfo, $this->warningsDuringStep);
        $this->stepInfo = [];
        $this->warningsDuringStep = 0;
    }

    public function debug(OutputFormat $formatter)
    {
        return $formatter->format($this->workflowState->getWorkflowName(), $this->stages);
    }

    public function attachStepInfo(string $info, array $context = []): void
    {
        $this->stepInfo[] = new StepInfo($info, $context);
    }

    public function addWarning(string $message, bool $workflowReportWarning = false): void
    {
        $this->warnings[$this->workflowState->getStage()][] = $message;

        if (!$workflowReportWarning) {
            $this->warningsDuringStep++;
        }
    }

    public function startExecution(): void
    {
        $this->startAt = microtime(true);
    }

    public function stopExecution(): void
    {
        $this->attachStepInfo('Execution time: ' . number_format(1000 * (microtime(true) - $this->startAt), 5) . 'ms');

        if ($this->warnings) {
            $warnings = sprintf(
                'Got %s warning%s during the execution:',
                $amount = count($this->warnings, COUNT_RECURSIVE) - count($this->warnings),
                $amount > 1 ? 's' : '',
            );

            foreach ($this->warnings as $stage => $stageWarnings) {
                $warnings .= implode(
                    '',
                    array_map(
                        fn (string $warning): string =>
                            sprintf(PHP_EOL . '        %s: %s', self::mapStage($stage), $warning),
                        $stageWarnings,
                    ),
                );
            }

            $this->attachStepInfo($warnings);
        }
    }

    public static function mapStage(int $stage): string
    {
        switch ($stage) {
            case WorkflowState::STAGE_PREPARE: return 'Prepare';
            case WorkflowState::STAGE_VALIDATE: return 'Validate';
            case WorkflowState::STAGE_BEFORE: return 'Before';
            case WorkflowState::STAGE_PROCESS: return 'Process';
            case WorkflowState::STAGE_ON_ERROR: return 'On Error';
            case WorkflowState::STAGE_ON_SUCCESS: return 'On Success';
            case WorkflowState::STAGE_AFTER: return 'After';
            case WorkflowState::STAGE_SUMMARY: return 'Summary';
        }
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getLastStep(): WorkflowStep
    {
        return $this->workflowState->getCurrentStep();
    }
}
