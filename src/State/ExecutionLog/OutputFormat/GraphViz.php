<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog\OutputFormat;

use PHPWorkflow\State\ExecutionLog\ExecutionLog;
use PHPWorkflow\State\ExecutionLog\Step;
use PHPWorkflow\State\ExecutionLog\StepInfo;
use PHPWorkflow\State\WorkflowResult;

class GraphViz implements OutputFormat
{
    private static int $stepIndex = 0;
    private static int $clusterIndex = 0;

    private static int $loopIndex = 0;
    private static array $loopInitialElement = [];
    private static array $loopLinks = [];

    public function format(string $workflowName, array $steps): string
    {
        $dotScript = "digraph \"$workflowName\" {\n";

        $dotScript .= $this->renderWorkflowGraph($workflowName, $steps);

        for ($i = 0; $i < self::$stepIndex - 1; $i++) {
            if (isset(self::$loopLinks[$i + 1])) {
                continue;
            }

            $dotScript .= sprintf("  %s -> %s\n", $i, $i + 1);
        }

        foreach (self::$loopLinks as $loopElement => $loopRoot) {
            $dotScript .= sprintf("  %s -> %s\n", $loopRoot, $loopElement);
        }

        $dotScript .= '}';

        return $dotScript;
    }

    private function renderWorkflowGraph(string $workflowName, array $steps): string
    {
        $dotScript = sprintf("  %s [label=\"$workflowName\"]\n", self::$stepIndex++);
        foreach ($steps as $stage => $stageSteps) {
            $dotScript .= sprintf(
                "  subgraph cluster_%s {\n    label = \"%s\"\n",
                self::$clusterIndex++,
                ExecutionLog::mapStage($stage)
            );

            /** @var Step $step */
            foreach ($stageSteps as $step) {
                foreach ($step->getStepInfo() as $info) {
                    switch ($info->getInfo()) {
                        case StepInfo::LOOP_START:
                            $dotScript .= sprintf(
                                "  subgraph cluster_loop_%s {\n    label = \"Loop\"\n",
                                self::$clusterIndex++
                            );

                            self::$loopInitialElement[++self::$loopIndex] = self::$stepIndex;

                            continue 2;
                        case StepInfo::LOOP_ITERATION:
                            self::$loopLinks[self::$stepIndex + 1] = self::$loopInitialElement[self::$loopIndex];

                            continue 2;
                        case StepInfo::LOOP_END:
                            $dotScript .= "\n}\n";
                            array_pop(self::$loopLinks);
                            self::$loopIndex--;

                            continue 2;
                        case StepInfo::NESTED_WORKFLOW:
                            /** @var WorkflowResult $nestedWorkflowResult */
                            $nestedWorkflowResult = $info->getContext()['result'];
                            $nestedWorkflowGraph = $nestedWorkflowResult->debug($this);

                            $lines = explode("\n", $nestedWorkflowGraph);
                            array_shift($lines);
                            array_pop($lines);

                            $dotScript .=
                                sprintf(
                                    "  subgraph cluster_%s {\n    label = \"Nested workflow\"\n",
                                    self::$clusterIndex++,
                                )
                                . preg_replace('/\d+ -> \d+\s*/m', '', join("\n", $lines))
                                . "\n}\n";

                            // TODO: additional infos. Currently skipped
                            continue 3;
                    }
                }

                $dotScript .= sprintf(
                    '    %s [label=%s shape="box" color="%s"]' . "\n",
                    self::$stepIndex++,
                    "<{$step->getDescription()} ({$step->getState()})"
                    . ($step->getReason() ? "<BR/><FONT POINT-SIZE=\"10\">{$step->getReason()}</FONT>" : '')
                    . join('', array_map(
                        fn (StepInfo $info): string => "<BR/><FONT POINT-SIZE=\"10\">{$info->getInfo()}</FONT>",
                        array_filter(
                            $step->getStepInfo(),
                            fn (StepInfo $info): bool => !in_array(
                                $info->getInfo(),
                                [
                                    StepInfo::LOOP_START,
                                    StepInfo::LOOP_ITERATION,
                                    StepInfo::LOOP_END,
                                    StepInfo::NESTED_WORKFLOW,
                                ],
                            )
                        ),
                    ))
                    . ">",
                    $this->mapColor($step),
                );
            }
            $dotScript .= "  }\n";
        }

        return $dotScript;
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
