<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\After;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextAfter
{
    public function after(WorkflowStep $step): After
    {
        return $this->next = (new After($this->workflow))->after($step);
    }
}
