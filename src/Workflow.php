<?php

declare(strict_types=1);

namespace PHPWorkflow;

use PHPWorkflow\Stage\Next\AllowNextPrepare;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextBefore;
use PHPWorkflow\Stage\Next\AllowNextProcess;
use PHPWorkflow\Stage\Next\AllowNextValidator;
use PHPWorkflow\Stage\Stage;

class Workflow extends Stage
{
    use
        AllowNextPrepare,
        AllowNextValidator,
        AllowNextBefore,
        AllowNextProcess;

    private string $name;

    public function __construct(string $name)
    {
        parent::__construct($this);

        $this->name = $name;
    }

    protected function run(WorkflowState $workflowState): ?Stage
    {
        $workflowState->setWorkflowName($this->name);

        $next = $this->next;
        while ($next) {
            $next = $next->run($workflowState);
        }

        if ($workflowState->getProcessException()) {
            throw $workflowState->getProcessException();
        }

        return null;
    }
}
