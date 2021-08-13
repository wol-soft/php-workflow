<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextExecuteWorkflow;
use PHPWorkflow\Step\WorkflowStep;

class After extends MultiStepStage
{
    use AllowNextExecuteWorkflow;

    const STAGE = WorkflowState::STAGE_AFTER;

    public function after(WorkflowStep $step): self
    {
        return $this->addStep($step);
    }
}
