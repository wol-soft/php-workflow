<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\ExecutionLog\Describable;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\WorkflowControl;

interface WorkflowStep extends Describable
{
    /**
     * Implement the logic for your step
     */
    public function run(WorkflowControl $control, WorkflowContainer $container): void;
}
