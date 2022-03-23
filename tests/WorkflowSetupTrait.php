<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use InvalidArgumentException;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\LoopControl;
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\WorkflowControl;

trait WorkflowSetupTrait
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

            public function run(WorkflowControl $control, WorkflowContainer $container): void
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

    public function failDataProvider(): array
    {
        return [
            'By Exception' => [function () {
                throw new InvalidArgumentException('Fail Message');
            }],
            'By failing step' => [fn (WorkflowControl $control) => $control->failStep('Fail Message')],
            'By failing workflow' => [fn (WorkflowControl $control) => $control->failWorkflow('Fail Message')],
        ];
    }
}
