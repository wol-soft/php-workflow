<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog\OutputFormat;

use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\ExecutionLog\Step;
use PHPWorkflow\State\ExecutionLog\StepInfo;
use PHPWorkflow\State\WorkflowResult;
use PHPWorkflow\State\WorkflowState;

class StringLog implements OutputFormat
{
    private string $indentation = '';

    public function format(string $workflowName, array $steps): string
    {
        $debug = "Process log for workflow '$workflowName':" . PHP_EOL;

        foreach ($steps as $stage => $stageSteps) {
            $debug .= sprintf(
                '%s%s%s:' . PHP_EOL,
                $stage === WorkflowState::STAGE_SUMMARY ? PHP_EOL : '',
                $this->indentation,
                ExecutionLog::mapStage($stage)
            );

            foreach ($stageSteps as $step) {
                $debug .= "{$this->indentation}  - " . $this->formatStep($step) . PHP_EOL;
            }
        }

        return trim($debug);
    }

    private function formatStep(Step $step): string
    {
        $stepLog = "{$step->getDescription()}: {$step->getState()}" .
            ($step->getReason() ? " ({$step->getReason()})" : '') .
            ($step->getWarnings()
                ? " ({$step->getWarnings()} warning" . ($step->getWarnings() > 1 ? 's' : '') . ")"
                : ''
            );

        foreach ($step->getStepInfo() as $info) {
            $formattedInfo = $this->formatInfo($info);

            if ($formattedInfo) {
                $stepLog .= PHP_EOL . $formattedInfo;
            }
        }

        return $stepLog;
    }

    private function formatInfo(StepInfo $info): ?string
    {
        switch ($info->getInfo()) {
            case StepInfo::NESTED_WORKFLOW:
                /** @var WorkflowResult $nestedWorkflowResult */
                $nestedWorkflowResult = $info->getContext()['result'];

                return "$this->indentation    - " . str_replace(
                    PHP_EOL . '      ' . PHP_EOL,
                    PHP_EOL . PHP_EOL,
                    str_replace(PHP_EOL, PHP_EOL . '      ', $nestedWorkflowResult->debug($this))
                );
            case StepInfo::LOOP_START:
                $this->indentation .= '  ';

                return null;
            case StepInfo::LOOP_ITERATION:
                return null;
            case StepInfo::LOOP_END:
                $this->indentation = substr($this->indentation, 0, -2);
                $iterations = $info->getContext()['iterations'];

                return "      - Loop finished after $iterations iteration" . ($iterations === 1 ? '' : 's');
            default: return "{$this->indentation}    - " . $info->getInfo();
        }
    }
}
