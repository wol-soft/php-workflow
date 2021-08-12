<?php

declare(strict_types=1);

namespace PHPWorkflow;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\Next\AllowNextBefore;
use PHPWorkflow\Step\Next\AllowNextProcess;
use PHPWorkflow\Step\Next\AllowNextValidator;
use PHPWorkflow\Step\Step;

class Workflow extends Step
{
    use
        AllowNextValidator,
        AllowNextBefore,
        AllowNextProcess;

    private string $name;

    public function __construct(string $name)
    {
        parent::__construct($this);

        $this->name = $name;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $this->next($workflowState);
    }
}
