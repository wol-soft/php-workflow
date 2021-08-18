<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\OnSuccess;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextOnSuccess
{
    public function onSuccess(WorkflowStep $step): OnSuccess
    {
        return $this->nextStage = (new OnSuccess($this->workflow))->onSuccess($step);
    }
}
