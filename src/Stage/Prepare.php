<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use PHPWorkflow\Stage\Next\AllowNextBefore;
use PHPWorkflow\Stage\Next\AllowNextProcess;
use PHPWorkflow\Stage\Next\AllowNextValidator;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\WorkflowStep;

class Prepare extends MultiStepStage
{
    use
        AllowNextValidator,
        AllowNextBefore,
        AllowNextProcess;

    const STAGE = WorkflowState::STAGE_PREPARE;

    public function prepare(WorkflowStep $step): self
    {
        return $this->addStep($step);
    }
}
