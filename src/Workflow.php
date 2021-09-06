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
    private array $middleware;

    public function __construct(string $name, callable ...$middlewares)
    {
        parent::__construct($this);

        $this->name = $name;
        $this->middleware = $middlewares;
    }

    protected function runStage(WorkflowState $workflowState): ?Stage
    {
        $workflowState->setWorkflowName($this->name);
        $workflowState->setMiddlewares($this->middleware);

        $nextStage = $this->nextStage;
        while ($nextStage) {
            $nextStage = $nextStage->runStage($workflowState);
        }

        if ($workflowState->getProcessException()) {
            throw $workflowState->getProcessException();
        }

        return null;
    }
}
