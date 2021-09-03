<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Validate;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextValidator
{
    public function validate(WorkflowStep $step, bool $hardValidator = false): Validate
    {
        return $this->nextStage = (new Validate($this->workflow))->validate($step, $hardValidator);
    }
}
