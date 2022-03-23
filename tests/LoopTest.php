<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\Loop;
use PHPWorkflow\Step\NestedWorkflow;
use PHPWorkflow\Workflow;
use PHPWorkflow\WorkflowControl;

class LoopTest extends TestCase
{
    use WorkflowTestTrait, WorkflowSetupTrait;

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
              - Start Loop: ok
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
              - Start Loop: ok
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
              - Start Loop: ok
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
              - Start Loop: ok
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

    public function testContinue(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b', 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) {
                                if ($container->get('entry') === 'b') {
                                    $control->continue('Skip reason');
                                }

                                $control->attachStepInfo('Process entry');
                            },
                        )
                    )
                    ->addStep($this->setupEmptyStep('Post-Process entry'))
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process-test: ok
                  - Process entry
                - Post-Process entry: ok
                - Loop iteration #1: ok
                - process-test: skipped (Skip reason)
                - Loop iteration #2: skipped (Skip reason)
                - process-test: ok
                  - Process entry
                - Post-Process entry: ok
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

    public function testContinueInLoopControl(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b', 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $entries = $container->get('entries');

                            if (empty($entries)) {
                                return false;
                            }

                            $entry = array_shift($entries);
                            $container->set('entries', $entries);

                            if ($entry === 'b') {
                                $control->continue('Skip reason');
                            }

                            return true;
                        },
                    )
                ))
                    ->addStep($this->setupEmptyStep('process 1'))
                    ->addStep($this->setupEmptyStep('process 2'))
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process 1: ok
                - process 2: ok
                - Loop iteration #1: ok
                - Loop iteration #2: skipped (Skip reason)
                - process 1: ok
                - process 2: ok
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

    public function testBreak(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b', 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) {
                                if ($container->get('entry') === 'b') {
                                    $control->break('break reason');
                                }

                                $control->attachStepInfo('Process entry');
                            },
                        )
                    )
                    ->addStep($this->setupEmptyStep('Post-Process entry'))
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process-test: ok
                  - Process entry
                - Post-Process entry: ok
                - Loop iteration #1: ok
                - process-test: skipped (break reason)
                - Loop iteration #2: skipped (break reason)
                - process-loop: ok
                  - Loop break in iteration #2
                  - Loop finished after 2 iterations
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testBreakInLoopControl(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', 'b', 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $entries = $container->get('entries');

                            if (empty($entries)) {
                                return false;
                            }

                            $entry = array_shift($entries);
                            $container->set('entries', $entries);

                            if ($entry === 'b') {
                                $control->break('Break reason');
                            }

                            return true;
                        },
                    )
                ))
                    ->addStep($this->setupEmptyStep('process 1'))
                    ->addStep($this->setupEmptyStep('process 2'))
            )
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process 1: ok
                - process 2: ok
                - Loop iteration #1: ok
                - Loop iteration #2: skipped (Break reason)
                - process-loop: ok
                  - Loop break in iteration #2
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
              - Start Loop: ok
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
              - Start Loop: ok
                - process-loop: skipped (skip reason)
                  - Loop finished after 1 iteration
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider continueOnErrorDataProvider test with both settings as skipWorkflow must work independently of the
     *                                           selected error handling mode
     */
    public function testSkipWorkflowInLoop(bool $continueOnError): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl(), $continueOnError))
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
              - Start Loop: ok
                - process-test: ok
                - Loop iteration #1: ok
                - process-test: skipped (no entry)
                - process-loop: skipped (no entry)
                  - Loop finished after 2 iterations
            
            Summary:
              - Workflow execution: skipped (no entry)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider continueOnErrorDataProvider test with both settings as skipWorkflow must work independently of the
     *                                           selected error handling mode
     */
    public function testSkipWorkflowInLoopControl(bool $continueOnError): void
    {
        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        fn (WorkflowControl $control) => $control->skipWorkflow('skip reason'),
                    ),
                    $continueOnError,
                ))
                    ->addStep($this->processEntry())
            )
            ->executeWorkflow();

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process-loop: skipped (skip reason)
                  - Loop finished after 1 iteration
            
            Summary:
              - Workflow execution: skipped (skip reason)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failDataProvider
     */
    public function testFailInLoop(callable $failingStep): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl()))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) use ($failingStep) {
                                if (!$container->get('entry')) {
                                    $control->attachStepInfo(
                                        sprintf('got value %s', var_export($container->get('entry'), true))
                                    );

                                    $failingStep($control);
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
              - Start Loop: ok
                - process-test: ok
                - Loop iteration #1: ok
                - process-test: failed (Fail Message)
                  - got value NULL
                - process-loop: failed (Fail Message)
                  - Loop finished after 2 iterations
            
            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failStepDataProvider
     */
    public function testFailInLoopWithContinueOnError(callable $failingStep): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl(), true))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container) use ($failingStep) {
                                if (!$container->get('entry')) {
                                    $failingStep($control);
                                }
                            },
                        )
                    )
            )
            ->executeWorkflow($container, false);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process-test: ok
                - Loop iteration #1: ok
                - process-test: failed (Fail Message)
                - Loop iteration #2: failed (Fail Message) (1 warning)
                - process-test: ok
                - Loop iteration #3: ok
                - process-loop: ok
                  - Loop finished after 3 iterations
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
                - Got 1 warning during the execution:
                    Process: Loop iteration #2 failed. Continued execution.
            DEBUG,
            $result,
        );
    }

    public function testFailWorkflowInLoopWithContinueOnError(): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->entryLoopControl(), true))
                    ->addStep(
                        $this->setupStep(
                            'process-test',
                            function (WorkflowControl $control, WorkflowContainer $container): void {
                                if (!$container->get('entry')) {
                                    $control->failWorkflow('Fail Message');
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
              - Start Loop: ok
                - process-test: ok
                - Loop iteration #1: ok
                - process-test: failed (Fail Message)
                - process-loop: failed (Fail Message)
                  - Loop finished after 2 iterations
            
            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failDataProvider
     */
    public function testFailInLoopControl(callable $failingStep): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop($this->setupLoop('process-loop', $failingStep)))
                    ->addStep($this->processEntry())
            )
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());
        $this->assertNotNull($result->getException());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process-loop: failed (Fail Message)
                  - Loop finished after 1 iteration
            
            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failStepDataProvider
     */
    public function testFailInLoopControlWithContinueOnError(callable $failingStep): void
    {
        $container = (new WorkflowContainer())->set('entries', ['a', null, 'c']);

        $result = (new Workflow('test'))
            ->process(
                (new Loop(
                    $this->setupLoop(
                        'process-loop',
                        function (WorkflowControl $control, WorkflowContainer $container) use ($failingStep): bool {
                            $entries = $container->get('entries');

                            if (empty($entries)) {
                                return false;
                            }

                            $container->set('entry', array_shift($entries));
                            $container->set('entries', $entries);

                            if (!$container->get('entry')) {
                                $failingStep($control);
                            }

                            return true;
                        },
                    ),
                    true,
                ))
                    ->addStep($this->processEntry())
            )
            ->executeWorkflow($container, false);

        $this->assertTrue($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - Start Loop: ok
                - process-test: ok
                  - Process entry a
                - Loop iteration #1: ok
                - Loop iteration #2: failed (Fail Message) (1 warning)
                - process-test: ok
                  - Process entry c
                - Loop iteration #3: ok
                - process-loop: ok
                  - Loop finished after 3 iterations
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
                - Got 1 warning during the execution:
                    Process: Loop iteration #2 failed. Continued execution.
            DEBUG,
            $result,
        );
    }

    public function continueOnErrorDataProvider(): array
    {
        return [
            "Default (don't continue)" => [false],
            "Continue on failure" => [true],
        ];
    }


    public function failStepDataProvider(): array
    {
        return [
            'By Exception' => [function (): void {
                throw new InvalidArgumentException('Fail Message');
            }],
            'By failing step' => [fn (WorkflowControl $control) => $control->failStep('Fail Message')],
        ];
    }
}
