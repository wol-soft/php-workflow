<?php

declare(strict_types=1);

namespace PHPWorkflow;

use PHPWorkflow\Step\WorkflowStep;

class Validator
{
    private WorkflowStep $step;
    private bool $hardValidator;

    public function __construct(WorkflowStep $step, bool $hardValidator)
    {
        $this->step = $step;
        $this->hardValidator = $hardValidator;
    }

    public function getStep(): WorkflowStep
    {
        return $this->step;
    }

    public function isHardValidator(): bool
    {
        return $this->hardValidator;
    }
}
