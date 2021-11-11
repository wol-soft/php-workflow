<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\StepExecutionTrait;
use PHPWorkflow\Workflow;

abstract class Stage
{
    use StepExecutionTrait;

    protected ?Stage $nextStage = null;
    protected Workflow $workflow;

    public function __construct(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    abstract protected function runStage(WorkflowState $workflowState): ?Stage;
}
