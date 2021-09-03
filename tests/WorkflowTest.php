<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPWorkflow\Exception\WorkflowControl\FailWorkflowException;
use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\Exception\WorkflowValidationException;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\NestedWorkflow;
use PHPWorkflow\Workflow;
use PHPWorkflow\WorkflowControl;

class WorkflowTest extends TestCase
{
    use WorkflowTestTrait;

    public function testMinimalWorkflow(): void
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
            Process log for workflow 'test':
            Process:
              - process-test: ok
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testAdditionalInformation(): void
    {
        $result = (new Workflow('test'))
            ->process($this->setupStep('process-test', function (WorkflowControl $control) {
                $control->attachStepInfo('Info 1');
                $control->attachStepInfo('Info 2');
            }))
            ->executeWorkflow();

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-test: ok
                - Info 1
                - Info 2

            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testWarningsEmittedFromStep(): void
    {
        $result = (new Workflow('test'))
            ->before($this->setupStep(
                'before-test',
                function (WorkflowControl $control) use (&$exceptionLineNamespacedException) {
                    $control->warning('before-warning', new FailWorkflowException('Fail-Message'));
                    $exceptionLineNamespacedException = __LINE__ - 1;
                },
            ))
            ->process($this->setupStep(
                'process-test',
                function (WorkflowControl $control) use (&$exceptionLineGlobalException) {
                    $control->warning('process-warning');
                    $control->warning('process-warning2', new InvalidArgumentException('Global-Exception-Message'));
                    $exceptionLineGlobalException = __LINE__ - 1;
                },
            ))
            ->executeWorkflow();

        $file = __FILE__;

        $this->assertTrue($result->success());
        $this->assertTrue($result->hasWarnings());
        $this->assertSame($result->getWarnings(), [
            'Before' => [
                "before-warning (FailWorkflowException: Fail-Message in $file::$exceptionLineNamespacedException)",
            ],
            'Process' => [
                'process-warning',
                "process-warning2 (InvalidArgumentException: Global-Exception-Message in $file::$exceptionLineGlobalException)",
            ],
        ]);

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - before-test: ok (1 warning)
            Process:
              - process-test: ok (2 warnings)
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
                - Got 3 warnings during the execution:
                    Before: before-warning (FailWorkflowException: Fail-Message in $file::$exceptionLineNamespacedException)
                    Process: process-warning
                    Process: process-warning2 (InvalidArgumentException: Global-Exception-Message in $file::$exceptionLineGlobalException)
            DEBUG,
            $result,
        );
    }

    public function testFailingWorkflowThrowsAnExceptionByDefault(): void
    {
        try {
            (new Workflow('test'))
                ->process($this->setupStep('process-test', function () {
                    throw new InvalidArgumentException('exception-message');
                }))
                ->executeWorkflow();

            $this->fail('Exception not thrown');
        } catch (WorkflowException $exception) {
            $this->assertSame("Workflow 'test' failed", $exception->getMessage());

            $result = $exception->getWorkflowResult();
            $this->assertFalse($result->success());

            $this->assertDebugLog(
                <<<DEBUG
                Process log for workflow 'test':
                Process:
                  - process-test: failed (exception-message)

                Summary:
                  - Workflow execution: failed (exception-message)
                    - Execution time: *
                DEBUG,
                $result,
            );
        }
    }

    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailingPreparationCancelsWorkflow(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->prepare($this->setupEmptyStep('prepare-test1'))
            ->prepare($this->setupStep('prepare-test2', $failingStep))
            ->prepare($this->setupEmptyStep('prepare-test3'))
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());
        $this->assertNotNull($result->getException());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Prepare:
              - prepare-test1: ok
              - prepare-test2: failed (Fail Message)

            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailingHardValidationCancelsWorkflow(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->validate($this->setupEmptyStep('validate-test1'), true)
            ->validate($this->setupStep('validate-test2', $failingStep), true)
            ->validate($this->setupEmptyStep('validate-test3'), true)
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());
        $this->assertNotNull($result->getException());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Validate:
              - validate-test1: ok
              - validate-test2: failed (Fail Message)

            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailingSoftValidationsAreCollected(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->validate($this->setupStep('validate-test1', $failingStep))
            ->validate($this->setupEmptyStep('validate-test2'))
            ->validate($this->setupStep('validate-test3', $failingStep))
            // hard validator must be executed first
            ->validate($this->setupEmptyStep('validate-test4'), true)
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Validate:
              - validate-test4: ok
              - validate-test1: failed (Fail Message)
              - validate-test2: ok
              - validate-test3: failed (Fail Message)

            Summary:
              - Workflow execution: failed
                - Execution time: *
            DEBUG,
            $result,
        );

        $exception = $result->getException();
        $this->assertInstanceOf(WorkflowValidationException::class, $exception);
        $this->assertCount(2, $exception->getValidationErrors());

        foreach ($exception->getValidationErrors() as $validationError) {
            $this->assertSame('Fail Message', $validationError->getMessage());
        }
    }


    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailingBeforeCancelsWorkflow(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->before($this->setupEmptyStep('before-test1'))
            ->before($this->setupStep('before-test2', $failingStep))
            ->before($this->setupEmptyStep('before-test3'))
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());
        $this->assertNotNull($result->getException());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - before-test1: ok
              - before-test2: failed (Fail Message)

            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailingProcess(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->before($this->setupEmptyStep('before-test'))
            ->process($this->setupStep('process-test', $failingStep))
            ->process($this->setupEmptyStep('process-test2'))
            ->onSuccess($this->setupEmptyStep('success-test1'))
            ->onSuccess($this->setupEmptyStep('success-test2'))
            ->onError($this->setupEmptyStep('error-test1'))
            ->onError($this->setupEmptyStep('error-test2'))
            ->after($this->setupEmptyStep('after-test1'))
            ->after($this->setupEmptyStep('after-test2'))
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - before-test: ok
            Process:
              - process-test: failed (Fail Message)
            On Error:
              - error-test1: ok
              - error-test2: ok
            After:
              - after-test1: ok
              - after-test2: ok

            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testSuccessfulProcess(): void
    {
        $result = (new Workflow('test'))
            ->before($this->setupEmptyStep('before-test'))
            ->process($this->setupEmptyStep('process-test1'))
            ->process($this->setupEmptyStep('process-test2'))
            ->onSuccess($this->setupEmptyStep('success-test1'))
            ->onSuccess($this->setupEmptyStep('success-test2'))
            ->onError($this->setupEmptyStep('error-test1'))
            ->onError($this->setupEmptyStep('error-test2'))
            ->after($this->setupEmptyStep('after-test1'))
            ->after($this->setupEmptyStep('after-test2'))
            ->executeWorkflow(null, false);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - before-test: ok
            Process:
              - process-test1: ok
              - process-test2: ok
            On Success:
              - success-test1: ok
              - success-test2: ok
            After:
              - after-test1: ok
              - after-test2: ok

            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailuresAfterProcessDontAffectOtherStepsForFailedProcess(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->process($this->setupStep('process-test', $failingStep))
            ->onError($this->setupStep('error-test1', $failingStep))
            ->onError($this->setupEmptyStep('error-test2'))
            ->after($this->setupStep('after-test1', $failingStep))
            ->after($this->setupEmptyStep('after-test2'))
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());
        $this->assertTrue($result->hasWarnings());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-test: failed (Fail Message)
            On Error:
              - error-test1: failed (Fail Message)
              - error-test2: ok
            After:
              - after-test1: failed (Fail Message)
              - after-test2: ok

            Summary:
              - Workflow execution: failed (Fail Message)
                - Execution time: *
                - Got 2 warnings during the execution:
                    On Error: Step failed (anonClass)
                    After: Step failed (anonClass)
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider failingStepDataProvider
     */
    public function testFailuresAfterProcessDontAffectOtherStepsForSuccessfulProcess(callable $failingStep): void
    {
        $result = (new Workflow('test'))
            ->process($this->setupEmptyStep('process-test'))
            ->onSuccess($this->setupStep('success-test1', $failingStep))
            ->onSuccess($this->setupEmptyStep('success-test2'))
            ->after($this->setupStep('after-test1', $failingStep))
            ->after($this->setupEmptyStep('after-test2'))
            ->executeWorkflow(null, false);

        $this->assertTrue($result->success());
        $this->assertTrue($result->hasWarnings());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - process-test: ok
            On Success:
              - success-test1: failed (Fail Message)
              - success-test2: ok
            After:
              - after-test1: failed (Fail Message)
              - after-test2: ok

            Summary:
              - Workflow execution: ok
                - Execution time: *
                - Got 2 warnings during the execution:
                    On Success: Step failed (anonClass)
                    After: Step failed (anonClass)
            DEBUG,
            $result,
        );
    }

    public function failingStepDataProvider(): array
    {
        return [
            'By Exception' => [function (WorkflowControl $control) {
                throw new InvalidArgumentException('Fail Message');
            }],
            'By failing step' => [fn (WorkflowControl $control) => $control->failStep('Fail Message')],
            'By failing workflow' => [fn (WorkflowControl $control) => $control->failWorkflow('Fail Message')],
        ];
    }

    public function testInjectingWorkflowContainer(): void
    {
        $container = (new WorkflowContainer())->set('testdata', 'Hello World');

        $result = (new Workflow('test'))
            ->before($this->setupStep(
                'before-test',
                fn (WorkflowControl $control, WorkflowContainer $container) =>
                    $container->set('additional-data', 'Goodbye')
            ))
            ->process($this->setupStep(
                'process-test',
                fn (WorkflowControl $control, WorkflowContainer $container) =>
                    $control->attachStepInfo($container->get('testdata'))
            ))
            ->after($this->setupStep(
                'after-test',
                fn (WorkflowControl $control, WorkflowContainer $container) =>
                $control->attachStepInfo($container->get('additional-data'))
            ))
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - before-test: ok
            Process:
              - process-test: ok
                - Hello World
            After:
              - after-test: ok
                - Goodbye

            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testSkipStep(): void
    {
        $result = (new Workflow('test'))
            ->validate($this->setupStep(
                'validate-test1',
                fn (WorkflowControl $control) => $control->skipStep('skip-reason 1'),
            ), true)
            ->validate($this->setupEmptyStep('validate-test2'))
            ->validate($this->setupStep(
                'validate-test3',
                fn (WorkflowControl $control) => $control->skipStep('skip-reason 2'),
            ))
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow(null, false);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Validate:
              - validate-test1: skipped (skip-reason 1)
              - validate-test2: ok
              - validate-test3: skipped (skip-reason 2)
            Process:
              - process-test: ok

            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testSkipWorkflow(): void
    {
        $result = (new Workflow('test'))
            ->validate($this->setupStep(
                'validate-test1',
                fn (WorkflowControl $control) => $control->skipStep('skip-reason 1'),
            ), true)
            ->validate($this->setupEmptyStep('validate-test2'))
            ->validate($this->setupStep(
                'validate-test3',
                fn (WorkflowControl $control) => $control->skipWorkflow('skip-reason 2'),
            ))
            ->validate($this->setupEmptyStep('validate-test4'))
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow(null, false);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Validate:
              - validate-test1: skipped (skip-reason 1)
              - validate-test2: ok
              - validate-test3: skipped (skip-reason 2)

            Summary:
              - Workflow execution: skipped (skip-reason 2)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testNestedWorkflow(): void
    {
        $container = (new WorkflowContainer())->set('testdata', 'Hello World');

        $result = (new Workflow('test'))
            ->before(new NestedWorkflow(
                (new Workflow('nested-test'))
                    ->before($this->setupStep(
                        'nested-before-test',
                        fn (WorkflowControl $control, WorkflowContainer $container) =>
                            $container->set('additional-data', 'from-nested'),
                    ))
                    ->process($this->setupStep(
                        'nested-process-test',
                        fn (WorkflowControl $control, WorkflowContainer $container) =>
                            $control->attachStepInfo($container->get('testdata')),
                    ))
            ))
            ->process($this->setupStep(
                'process-test',
                fn (WorkflowControl $control, WorkflowContainer $container) =>
                    $control->attachStepInfo($container->get('additional-data')),
            ))
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - Execute nested workflow: ok
                - Process log for workflow 'nested-test':
                  Before:
                    - nested-before-test: ok
                  Process:
                    - nested-process-test: ok
                      - Hello World

                  Summary:
                    - Workflow execution: ok
                      - Execution time: *
            Process:
              - process-test: ok
                - from-nested

            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function testNestedWorkflowFails(): void
    {
        $container = (new WorkflowContainer())->set('testdata', 'Hello World');

        $result = (new Workflow('test'))
            ->before(new NestedWorkflow(
                (new Workflow('nested-test'))
                    ->process($this->setupStep(
                        'nested-process-test',
                        function () {
                            throw new InvalidArgumentException('exception-message');
                        },
                    ))
            ))
            ->process($this->setupEmptyStep('process-test'))
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - Execute nested workflow: failed (Nested workflow 'nested-test' failed)
                - Process log for workflow 'nested-test':
                  Process:
                    - nested-process-test: failed (exception-message)

                  Summary:
                    - Workflow execution: failed (exception-message)
                      - Execution time: *

            Summary:
              - Workflow execution: failed (Nested workflow 'nested-test' failed)
                - Execution time: *
            DEBUG,
            $result,
        );
    }
}
