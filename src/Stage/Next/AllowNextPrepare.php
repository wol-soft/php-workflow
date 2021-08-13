<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Prepare;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextPrepare
{
    public function prepare(WorkflowStep $step): Prepare
    {
        return $this->next = (new Prepare($this->workflow))->before($step);
    }
}
