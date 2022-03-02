<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog\OutputFormat;

use PHPWorkflow\State\ExecutionLog\Step;
use PHPWorkflow\State\ExecutionLog\StepInfo;
use PHPWorkflow\State\WorkflowResult;

class StringLog implements OutputFormat
{
    public function format(string $workflowName, array $steps): string
    {
        $debug = "Process log for workflow '$workflowName':" . PHP_EOL;

        foreach ($steps as $stage => $stageSteps) {
            $debug .= "$stage:" . PHP_EOL;

            foreach ($stageSteps as $step) {
                $debug .= '  - ' . $this->formatStep($step) . PHP_EOL;
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
            $stepLog .= PHP_EOL . "    - " . $this->formatInfo($info);
        }

        return $stepLog;
    }

    private function formatInfo(StepInfo $info): string
    {
        switch ($info->getInfo()) {
            case StepInfo::NESTED_WORKFLOW:
                /** @var WorkflowResult $nestedWorkflowResult */
                $nestedWorkflowResult = $info->getContext()['result'];

                return str_replace(
                    PHP_EOL . '      ' . PHP_EOL,
                    PHP_EOL . PHP_EOL,
                    str_replace(PHP_EOL, PHP_EOL . '      ', $nestedWorkflowResult->debug($this)),
                );
            default: return $info->getInfo();
        }
    }
}
