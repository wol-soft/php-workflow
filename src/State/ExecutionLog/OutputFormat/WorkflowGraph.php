<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog\OutputFormat;

use Exception;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\ExecutionLog\Step;
use PHPWorkflow\State\ExecutionLog\StepInfo;

class WorkflowGraph implements OutputFormat
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function format(string $workflowName, array $steps): string
    {
        $dotScript = "digraph \"$workflowName\" {\n";
        $stepIndex = 0;
        $stageIndex = 0;

        $dotScript .= sprintf("  %s [label=\"$workflowName\"]\n", $stepIndex++);
        foreach ($steps as $stage => $stageSteps) {
            $dotScript .= sprintf("  subgraph cluster_%s {\n    label = $stage", $stageIndex++);
            /** @var Step $step */
            foreach ($stageSteps as $step) {
                $dotScript .= sprintf(
                    '    %s [label=%s shape="box" color="%s"]' . "\n",
                    $stepIndex++,
                    "<{$step->getDescription()} ({$step->getState()})"
                        . ($step->getReason() ? "<BR/><FONT POINT-SIZE=\"10\">{$step->getReason()}</FONT>" : '')
                        . join('', array_map(
                            fn (StepInfo $info): string => "<BR/><FONT POINT-SIZE=\"10\">{$info->getInfo()}</FONT>",
                            $step->getStepInfo(),
                        ))
                        . ">",
                    $this->mapColor($step),
                );
            }
            $dotScript .= "  }\n";
        }

        for ($i = 0; $i < $stepIndex - 1; $i++) {
            $dotScript .= sprintf("  %s -> %s\n", $i, $i + 1);
        }
        $dotScript .= '}';

        $this->generateImageFromScript(
            $dotScript,
            $filePath = $this->path . DIRECTORY_SEPARATOR . $workflowName . '_' . uniqid() . '.svg',
        );

        return $filePath;
    }

    private function generateImageFromScript(string $script, string $file)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'graphviz');
        if ($tmp === false) {
            throw new Exception('Unable to get temporary file name for graphviz script');
        }

        $ret = file_put_contents($tmp, $script, LOCK_EX);
        if ($ret === false) {
            throw new Exception('Unable to write graphviz script to temporary file');
        }

        $ret = 0;

        system('dot -T svg ' . escapeshellarg($tmp) . ' -o ' . escapeshellarg($file), $ret);
        if ($ret !== 0) {
            throw new Exception('Unable to invoke "dot" to create image file (code ' . $ret . ')');
        }

        unlink($tmp);
    }

    private function mapColor(Step $step): string
    {
        if ($step->getState() === ExecutionLog::STATE_SUCCESS && $step->getWarnings()) {
            return 'yellow';
        }

        return [
            ExecutionLog::STATE_SUCCESS => 'green',
            ExecutionLog::STATE_SKIPPED => 'grey',
            ExecutionLog::STATE_FAILED => 'red',
        ][$step->getState()];
    }
}
