<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\Stage\Next\AllowNextProcess;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\WorkflowStep;

class Before extends MultiStepStage
{
    use AllowNextProcess;

    const STAGE = WorkflowState::STAGE_BEFORE;

    public function before(WorkflowStep $step): self
    {
        return $this->addStep($step);
    }
}
