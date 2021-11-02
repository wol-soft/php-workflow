<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\Loop;
use PHPWorkflow\Workflow;
use PHPWorkflow\WorkflowControl;

class LoopTest extends TestCase
{
    use WorkflowTestTrait;

    public function testLoop(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b', 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        fn (WorkflowControl $control, WorkflowContainer $container) =>
                            !empty($container->get('entries'))
                    ),
                ))->addStep(
                    $this->setupStep(
                        'process-test',
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $entries = $container->get('entries');
                            $entry = array_shift($entries);
                            $container->set('entries', $entries);

                            $control->attachStepInfo("Process entry $entry");
                        },
                    )
                )
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-test: ok
                - Process entry a
              - Loop iteration #1: ok
              - process-test: ok
                - Process entry b
              - Loop iteration #2: ok
              - process-test: ok
                - Process entry c
              - Loop iteration #3: ok
              - process-loop: ok
                - Loop finished after 3 iterations
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testEmptyLoop()
    {
        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->setupLoop('process-loop', fn () => false)))
                    ->addStep($this->setupEmptyStep('process-test')),
            )
            ->executeWorkflow();

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-loop: ok
                - Loop finished after 0 iterations
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testMultipleStepsInLoop(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        function (WorkflowControl $control, WorkflowContainer $container): bool {
                            $entries = $container->get('entries');

                            if (empty($entries)) {
                                return false;
                            }

                            $container->set('entry', array_shift($entries));
                            $container->set('entries', $entries);

                            return true;
                        },
                    ),
                ))->addStep(
                    $this->setupStep(
                        'process-test',
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $control->attachStepInfo("Process entry " . $container->get('entry'));
                        },
                    ),
                )->addStep($this->setupEmptyStep('process-test-2')),
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-test: ok
                - Process entry a
              - process-test-2: ok
              - Loop iteration #1: ok
              - process-test: ok
                - Process entry b
              - process-test-2: ok
              - Loop iteration #2: ok
              - process-loop: ok
                - Loop finished after 2 iterations
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }
}
