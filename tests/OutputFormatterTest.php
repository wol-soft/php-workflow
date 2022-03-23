<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\ExecutionLog\OutputFormat\GraphViz;
use PHPWorkflow\State\ExecutionLog\OutputFormat\WorkflowGraph;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\Loop;
use PHPWorkflow\Step\NestedWorkflow;
use PHPWorkflow\Workflow;
use PHPWorkflow\WorkflowControl;

/**
 * Class OutputFormatterTest
 *
 * @package PHPWorkflow\Tests
 */
class OutputFormatterTest extends TestCase
{
    use WorkflowTestTrait, WorkflowSetupTrait;

    public function testGraphVizOutputFormatter(): void
    {
        $result = (new Workflow('test'))
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow();

        $this->assertTrue($result->success());
        $this->assertFalse($result->hasWarnings());
        $this->assertEmpty($result->getWarnings());
        $this->assertNull($result->getException());
        $this->assertSame('test', $result->getWorkflowName());

        $this->assertDebugLog(
            <<<DEBUG
            digraph "test" {
              0 [label="test"]
              subgraph cluster_0 {
                label = "Process"
                1 [label=<process-test (ok)> shape="box" color="green"]
              }
              subgraph cluster_1 {
                label = "Summary"
                2 [label=<Workflow execution (ok)<BR/><FONT POINT-SIZE="10">Execution time: *</FONT>> shape="box" color="green"]
              }
              0 -> 1
              1 -> 2
            }
            DEBUG,
            $result,
            new GraphViz(),
        );
    }
}
