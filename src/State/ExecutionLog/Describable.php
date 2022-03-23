<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

/**
 * Interface Describable
 *
 * @package PHPWorkflow\State\ExecutionLog
 */
interface Describable
{
    /**
     * Describe in a few words what this step does
     */
    public function getDescription(): string;
}