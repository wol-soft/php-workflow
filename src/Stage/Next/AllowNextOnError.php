<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\OnError;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextOnError
{
    public function onError(WorkflowStep $step): OnError
    {
        return $this->next = (new OnError($this->workflow))->onError($step);
    }
}
