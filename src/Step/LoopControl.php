<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\WorkflowControl;

interface LoopControl
{
    /**
     * Describe in a few words what this loop does
     */
    public function getDescription(): string;

    /**
     * Return true if the next iteration of the loop shall be executed. Return false to break the loop
     */
    public function executeNextIteration(int $iteration, WorkflowControl $control, WorkflowContainer $container): bool;
}
