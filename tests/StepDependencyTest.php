<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\Dependency\Requires;
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\Workflow;
use PHPWorkflow\WorkflowControl;

class StepDependencyTest extends TestCase
{
    use WorkflowTestTrait;

    public static function setUpBeforeClass(): void
    {
        if (PHP_MAJOR_VERSION < 8) {
            self::markTestSkipped('Step dependencies require PHP >= 8.0.0');
        }
    }

    public function testMissingKeyFails(): void
    {
        $result = (new Workflow('test'))
            ->process($this->requireCustomerIdStep())
            ->executeWorkflow(null, false);

        $this->assertFalse($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with simple require: failed (Missing 'customerId' in container)
            
            Summary:
              - Workflow execution: failed (Missing 'customerId' in container)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider providedKeyDataProvider
     */
    public function testProvidedKeySucceeds($input): void
    {
        $result = (new Workflow('test'))
            ->process($this->requireCustomerIdStep())
            ->executeWorkflow((new WorkflowContainer())->set('customerId', $input));

        $this->assertTrue($result->success());

        $type = gettype($input);
        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with simple require: ok
                - provided type: $type
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function providedKeyDataProvider(): array
    {
        return [
            'null' => [null],
            'int' => [10],
            'float' => [10.5],
            'bool' => [true],
            'array' => [[1, 2, 3]],
            'string' => ['Hello'],
            'DateTime' => [new DateTime()],
        ];
    }

    /**
     * @dataProvider invalidTypedValueDataProvider
     */
    public function testInvalidTypedValueFails(WorkflowContainer $container, string $expectedExceptionMessage): void
    {
        $result = (new Workflow('test'))
            ->process($this->requiredTypedCustomerIdStep())
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with typed require: failed ($expectedExceptionMessage)
            
            Summary:
              - Workflow execution: failed ($expectedExceptionMessage)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function invalidTypedValueDataProvider(): iterable
    {
        yield 'value not provided' => [(new WorkflowContainer()), "Missing 'customerId' in container"];

        foreach ([null, false, 10, 0.0, []] as $input) {
            yield gettype($input) => [
                (new WorkflowContainer())->set('customerId', $input),
                "Value for 'customerId' has an invalid type. Expected string, got " . gettype($input),
            ];
        }
    }

    public function testProvidedTypedKeySucceeds(): void
    {
        $result = (new Workflow('test'))
            ->process($this->requiredTypedCustomerIdStep())
            ->executeWorkflow((new WorkflowContainer())->set('customerId', 'Hello'));

        $this->assertTrue($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with typed require: ok
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    /**
     * @dataProvider invalidNullableTypedValueDataProvider
     */
    public function testInvalidNullableTypedValueFails(
        WorkflowContainer $container,
        string $expectedExceptionMessage
    ): void {
        $result = (new Workflow('test'))
            ->process($this->requiredNullableTypedCustomerIdStep())
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with nullable typed require: failed ($expectedExceptionMessage)
            
            Summary:
              - Workflow execution: failed ($expectedExceptionMessage)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function invalidNullableTypedValueDataProvider(): iterable
    {
        yield 'value not provided' => [(new WorkflowContainer()), "Missing 'customerId' in container"];

        foreach ([false, 10, 0.0, []] as $input) {
            yield gettype($input) => [
                (new WorkflowContainer())->set('customerId', $input),
                "Value for 'customerId' has an invalid type. Expected ?string, got " . gettype($input),
            ];
        }
    }

    /**
     * @dataProvider providedNullableTypedKeyDataProvider
     */
    public function testProvidedNullableTypedKeySucceeds(?string $input): void
    {
        $result = (new Workflow('test'))
            ->process($this->requiredNullableTypedCustomerIdStep())
            ->executeWorkflow((new WorkflowContainer())->set('customerId', $input));

        $this->assertTrue($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with nullable typed require: ok
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function providedNullableTypedKeyDataProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'numeric string' => ['123'],
            'string' => ['Hello World'],
        ];
    }

    /**
     * @dataProvider invalidDateTimeValueDataProvider
     */
    public function testInvalidDateTimeValueFails(
        WorkflowContainer $container,
        string $expectedExceptionMessage
    ): void {
        $result = (new Workflow('test'))
            ->process($this->requiredDateTimeStep())
            ->executeWorkflow($container, false);

        $this->assertFalse($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with DateTime require: failed ($expectedExceptionMessage)
            
            Summary:
              - Workflow execution: failed ($expectedExceptionMessage)
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function invalidDateTimeValueDataProvider(): iterable
    {
        yield 'created not provided' => [(new WorkflowContainer()), "Missing 'created' in container"];
        yield 'updated not provided' => [
            (new WorkflowContainer())->set('created', new DateTime()),
            "Missing 'updated' in container",
        ];

        foreach ([null, false, 10, 0.0, [], '', new DateTimeZone('Europe/Berlin')] as $input) {
            yield 'Invalid value for created - ' . gettype($input) => [
                (new WorkflowContainer())->set('created', $input),
                "Value for 'created' has an invalid type. Expected DateTime, got "
                    . gettype($input)
                    . (is_object($input) ? sprintf(' (%s)', get_class($input)) : ''),
            ];
        }

        foreach ([false, 10, 0.0, [], '', new DateTimeZone('Europe/Berlin')] as $input) {
            yield 'Invalid value for updated - ' . gettype($input) => [
                (new WorkflowContainer())->set('created', new DateTime())->set('updated', $input),
                "Value for 'updated' has an invalid type. Expected ?DateTime, got "
                    . gettype($input)
                    . (is_object($input) ? sprintf(' (%s)', get_class($input)) : ''),
            ];
        }
    }


    /**
     * @dataProvider providedDateTimeDataProvider
     */
    public function testProvidedDateTimeSucceeds(DateTime $created, ?DateTime $updated): void
    {
        $result = (new Workflow('test'))
            ->process($this->requiredDateTimeStep())
            ->executeWorkflow((new WorkflowContainer())->set('created', $created)->set('updated', $updated));

        $this->assertTrue($result->success());

        $this->assertDebugLog(
            <<<DEBUG
            Process log for workflow 'test':
            Process:
              - test step with DateTime require: ok
            
            Summary:
              - Workflow execution: ok
                - Execution time: *
            DEBUG,
            $result,
        );
    }

    public function providedDateTimeDataProvider(): array
    {
        return [
            'only created' => [new DateTime(), null],
            'created and updated' => [new DateTime(), new DateTime()],
        ];
    }

    public function requireCustomerIdStep(): WorkflowStep
    {
        return new class () implements WorkflowStep {
            public function getDescription(): string
            {
                return 'test step with simple require';
            }

            public function run(
                WorkflowControl $control,
                #[Requires('customerId')]
                WorkflowContainer $container
            ): void {
                $control->attachStepInfo('provided type: ' . gettype($container->get('customerId')));
            }
        };
    }

    public function requiredTypedCustomerIdStep(): WorkflowStep
    {
        return new class () implements WorkflowStep {
            public function getDescription(): string
            {
                return 'test step with typed require';
            }

            public function run(
                WorkflowControl $control,
                #[Requires('customerId', 'string')]
                WorkflowContainer $container
            ): void {}
        };
    }

    public function requiredNullableTypedCustomerIdStep(): WorkflowStep
    {
        return new class () implements WorkflowStep {
            public function getDescription(): string
            {
                return 'test step with nullable typed require';
            }

            public function run(
                WorkflowControl $control,
                #[Requires('customerId', '?string')]
                WorkflowContainer $container
            ): void {}
        };
    }

    public function requiredDateTimeStep(): WorkflowStep
    {
        return new class () implements WorkflowStep {
            public function getDescription(): string
            {
                return 'test step with DateTime require';
            }

            public function run(
                WorkflowControl $control,
                #[Requires('created', DateTime::class)]
                #[Requires('updated', '?' . DateTime::class)]
                WorkflowContainer $container
            ): void {}
        };
    }
}
