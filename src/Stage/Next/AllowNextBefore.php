<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Before;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextBefore
{
    public function before(WorkflowStep $step): Before
    {
        return $this->nextStage = (new Before($this->workflow))->before($step);
    }
}
