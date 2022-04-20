<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Dependency;

use PHPWorkflow\Exception\WorkflowStepDependencyNotFulfilledException;
use PHPWorkflow\State\WorkflowContainer;

interface StepDependencyInterface
{
    /**
     * @throws WorkflowStepDependencyNotFulfilledException
     */
    public function check(WorkflowContainer $container): void;
}
