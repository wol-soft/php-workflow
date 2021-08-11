<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\Workflow;

abstract class Step
{
    protected ?Step $next = null;
    protected Workflow $workflow;

    public function __construct(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    abstract protected function run(): void;

    protected function next(): void
    {
        if ($this->next) {
            $this->next->run();
        }
    }
}
