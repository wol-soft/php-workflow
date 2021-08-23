<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Process;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextProcess
{
    public function process(WorkflowStep $step): Process
    {
        return $this->nextStage = (new Process($this->workflow))->process($step);
    }
}
