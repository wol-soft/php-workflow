<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\Loop;
use PHPWorkflow\Step\LoopControl;
use PHPWorkflow\Step\NestedWorkflow;
use PHPWorkflow\Step\WorkflowStep;
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
                (new Loop($this->entryLoopControl()))
                    ->addStep($this->processEntry())
                    ->addStep($this->setupEmptyStep('process-test-2')),
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

    public function testNestedWorkflowInLoop(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        new NestedWorkflow(
                            (new Workflow('nested-workflow'))->process($this->processEntry()),
                        ),
                    ),
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Execute nested workflow: ok
                - Process log for workflow 'nested-workflow':
                  Process:
                    - process-test: ok
                      - Process entry a
            
                  Summary:
                    - Workflow execution: ok
                      - Execution time: *
              - Loop iteration #1: ok
              - Execute nested workflow: ok
                - Process log for workflow 'nested-workflow':
                  Process:
                    - process-test: ok
                      - Process entry b
            
                  Summary:
                    - Workflow execution: ok
                      - Execution time: *
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

    public function testSkipStepInLoop(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) {
                                if (!$container->get('entry')) {
                                    $control->skipStep('no entry');
                                }
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
              - Loop iteration #1: ok
              - process-test: skipped (no entry)
              - Loop iteration #2: ok
              - process-test: ok
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

    public function testSkipStepInLoopControl(): void
    {
        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        fn (WorkflowControl $control) => $control->skipStep('skip reason'),
                    )
                ))
                    ->addStep($this->processEntry())
            )
            ->executeWorkflow();

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-loop: skipped (skip reason)
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testSkipWorkflowInLoop(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) {
                                if (!$container->get('entry')) {
                                    $control->skipWorkflow('no entry');
                                }
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
              - Loop iteration #1: ok
              - process-test: skipped (no entry)
              - process-loop: skipped (no entry)
            
            Summary:
              - Workflow execution: skipped (no entry)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testSkipWorkflowInLoopControl(): void
    {
        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        fn (WorkflowControl $control) => $control->skipWorkflow('skip reason'),
                    )
                ))
                    ->addStep($this->processEntry())
            )
            ->executeWorkflow();

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-loop: skipped (skip reason)
            
            Summary:
              - Workflow execution: skipped (skip reason)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testFailInLoop(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) {
                                if (!$container->get('entry')) {
                                    $control->attachStepInfo(
                                        sprintf('got value %s', var_export($container->get('entry'), true))
                                    );

                                    $control->failStep('no entry');
                                }
                            },
                        )
                    )
            )
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());
        $this->assertNotNull($result->getException());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-test: ok
              - Loop iteration #1: ok
              - process-test: failed (no entry)
                - got value NULL
              - process-loop: failed (no entry)
            
            Summary:
              - Workflow execution: failed (no entry)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testFailInLoopControl(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        fn (WorkflowControl $control) => $control->failStep('fail reason'),
                    )
                ))
                    ->addStep($this->processEntry())
            )
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());
        $this->assertNotNull($result->getException());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-loop: failed (fail reason)
            
            Summary:
              - Workflow execution: failed (fail reason)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    private function entryLoopControl(): LoopControl
    {
        return $this->setupLoop(
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
        );
    }

    private function processEntry(): WorkflowStep
    {
        return $this->setupStep(
            'process-test',
            function (WorkflowControl $control, WorkflowContainer $container) {
                $control->attachStepInfo("Process entry " . $container->get('entry'));
            },
        );
    }
}
