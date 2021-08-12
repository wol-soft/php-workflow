<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

use Exception;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\WorkflowControl;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(bool $throwOnFailure = true): bool
    {
        try {
            $this->workflow->run(new WorkflowState(new WorkflowControl()));
        } catch (Exception $exception) {
            if ($throwOnFailure) {
                throw $exception;
            }

            return false;
        }

        return true;
    }
}
