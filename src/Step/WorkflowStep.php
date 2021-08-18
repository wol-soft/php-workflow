<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\WorkflowControl;

interface WorkflowStep
{
    /**
     * Implement the logic for your step
     */
    public function run(WorkflowControl $control, WorkflowContainer $container);

    /**
     * Describe in a few words what this step does
     */
    public function getDescription(): string;
}
