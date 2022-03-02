<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\NestedWorkflow;
use PHPWorkflow\Workflow;
use PHPWorkflow\WorkflowControl;

class NestedWorkflowTest extends TestCase
{
    use WorkflowTestTrait, WorkflowSetupTrait;

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
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $control->warning('nested-warning-message');
                            $control->attachStepInfo($container->get('testdata'));
                        },
                    ))
            ))
            ->process($this->setupStep(
                'process-test',
                function (WorkflowControl $control, WorkflowContainer $container) {
                    $control->warning('warning-message');
                    $control->attachStepInfo($container->get('additional-data'));
                },
            ))
            ->executeWorkflow($container);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - Execute nested workflow: ok (1 warning)
                - Process log for workflow 'nested-test':
                  Before:
                    - nested-before-test: ok
                  Process:
                    - nested-process-test: ok (1 warning)
                      - Hello World
            
                  Summary:
                    - Workflow execution: ok
                      - Execution time: *
                      - Got 1 warning during the execution:
                          Process: nested-warning-message
            Process:
              - process-test: ok (1 warning)
                - from-nested
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
                - Got 2 warnings during the execution:
                    Before: Nested workflow 'nested-test' emitted 1 warning
                    Process: warning-message
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

        /** @var NestedWorkflow $lastStep */
        $lastStep = $result->getLastStep();
        $this->assertInstanceOf(NestedWorkflow::class, $lastStep);
        $nestedResult = $lastStep->getNestedWorkflowResult();

        $this->assertFalse($nestedResult->success());
    }

    public function testNestedWorkflowHasMergedContainer(): void
    {
        $parentContainer = new class () extends WorkflowContainer {
            public function getParentData(): string
            {
                return 'parent-data';
            }
        };
        $parentContainer->set('set-parent', 'set-parent-data');

        $nestedContainer = new class () extends WorkflowContainer {
            public function getNestedData(): string
            {
                return 'nested-data';
            }
        };
        $nestedContainer->set('set-nested', 'set-nested-data');


        $result = (new Workflow('test'))
            ->before(new NestedWorkflow(
                (new Workflow('nested-test'))
                    ->before($this->setupStep(
                        'nested-before-test',
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $control->attachStepInfo($container->getParentData());
                            $control->attachStepInfo($container->getNestedData());
                            $control->attachStepInfo($container->get('set-parent'));
                            $control->attachStepInfo($container->get('set-nested'));

                            $container->set('additional-data', 'from-nested');
                        },
                    ))
                    ->process($this->setupStep(
                        'nested-process-test',
                        function (WorkflowControl $control, WorkflowContainer $container) {
                            $control->attachStepInfo($container->get('additional-data'));
                        },
                    )),
                $nestedContainer,
            ))
            ->process($this->setupStep(
                'process-test',
                function (WorkflowControl $control, WorkflowContainer $container) {
                    $control->attachStepInfo($container->get('additional-data'));
                    $control->attachStepInfo($container->get('set-parent'));
                    $control->attachStepInfo(var_export($container->get('set-nested'), true));
                },
            ))
            ->executeWorkflow($parentContainer);

        $this->assertTrue($result->success());
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Before:
              - Execute nested workflow: ok
                - Process log for workflow 'nested-test':
                  Before:
                    - nested-before-test: ok
                      - parent-data
                      - nested-data
                      - set-parent-data
                      - set-nested-data
                  Process:
                    - nested-process-test: ok
                      - from-nested
            
                  Summary:
                    - Workflow execution: ok
                      - Execution time: *
            Process:
              - process-test: ok
                - from-nested
                - set-parent-data
                - NULL
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );

        // test data is accessible via main container
        $this->assertSame('from-nested', $result->getContainer()->get('additional-data'));
        $this->assertSame('set-parent-data', $result->getContainer()->get('set-parent'));
        $this->assertNull($result->getContainer()->get('set-nested'));
    }
}
