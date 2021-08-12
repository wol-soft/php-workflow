<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\FailWorkflowException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\WorkflowControl;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(): void
    {
        try {
            $this->workflow->run(new WorkflowState(new WorkflowControl()));
        } catch (SkipWorkflowException | FailWorkflowException $exception) {

        } catch (Exception $exception) {

        }
    }
}
