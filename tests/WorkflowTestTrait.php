<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowResult;
use PHPWorkflow\Step\LoopControl;
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\WorkflowControl;

trait WorkflowTestTrait
{
    private function setupEmptyStep(string $description): WorkflowStep
    {
        return $this->setupStep($description, fn () => null);
    }

    private function setupStep(string $description, callable $callable): WorkflowStep
    {
        return new class ($description, $callable) implements WorkflowStep {
            private string $description;
            private $callable;

            public function __construct(string $description, callable $callable)
            {
                $this->description = $description;
                $this->callable = $callable;
            }

            public function getDescription(): string
            {
                return $this->description;
            }

            public function run(WorkflowControl $control, WorkflowContainer $container)
            {
                ($this->callable)($control, $container);
            }
        };
    }

    private function setupLoop(string $description, callable $callable): LoopControl
    {
        return new class ($description, $callable) implements LoopControl {
            private string $description;
            private $callable;

            public function __construct(string $description, callable $callable)
            {
                $this->description = $description;
                $this->callable = $callable;
            }

            public function getDescription(): string
            {
                return $this->description;
            }
            public function executeNextIteration(
                int $iteration,
                WorkflowControl $control,
                WorkflowContainer $container
            ): bool {
                return ($this->callable)($control, $container);
            }
        };
    }

    private function assertDebugLog(string $expected, WorkflowResult $result): void
    {
        $this->assertSame(
            $expected,
            preg_replace('#[\w\\\\]+@anonymous[^)]+#', 'anonClass', preg_replace('/[\d.]+ms/', '*', $result->debug())),
        );
    }
}
