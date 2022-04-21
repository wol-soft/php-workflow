<?php

declare(strict_types=1);

namespace PHPWorkflow\Middleware;

use PHPWorkflow\Exception\WorkflowStepDependencyNotFulfilledException;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\Step\Dependency\StepDependencyInterface;
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\WorkflowControl;
use ReflectionAttribute;
use ReflectionException;
use ReflectionMethod;

class WorkflowStepDependencyCheck
{
    /**
     * @throws ReflectionException
     * @throws WorkflowStepDependencyNotFulfilledException
     */
    public function __invoke(
        callable $next,
        WorkflowControl $control,
        WorkflowContainer $container,
        WorkflowStep $step,
    ) {
        $containerParameter = (new ReflectionMethod($step, 'run'))->getParameters()[1] ?? null;

        if ($containerParameter) {
            foreach ($containerParameter->getAttributes(
                    StepDependencyInterface::class,
                    ReflectionAttribute::IS_INSTANCEOF,
                ) as $dependencyAttribute
            ) {
                /** @var StepDependencyInterface $dependency */
                $dependency = $dependencyAttribute->newInstance();
                $dependency->check($container);
            }
        }

        return $next();
    }
}
