<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Validation;
use PHPWorkflow\Step\WorkflowStep;

trait AllowNextValidator
{
    public function validate(WorkflowStep $step, bool $hardValidator = false): Validation
    {
        return $this->next = (new Validation($this->workflow))->validate($step, $hardValidator);
    }
}
