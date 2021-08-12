<?php

declare(strict_types=1);

namespace PHPWorkflow;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextBefore;
use PHPWorkflow\Stage\Next\AllowNextProcess;
use PHPWorkflow\Stage\Next\AllowNextValidator;
use PHPWorkflow\Stage\Stage;

class Workflow extends Stage
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
