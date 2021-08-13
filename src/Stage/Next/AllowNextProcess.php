<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\AfterProcess;
use PHPWorkflow\Stage\Process;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextProcess
{
    public function process(WorkflowStep $step): AfterProcess
    {
        $this->next = (new Process($this->workflow))->process($step);

        return $this->next->getAfterProcess();
    }
}
