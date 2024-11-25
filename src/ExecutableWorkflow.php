<?php

namespace PHPWorkflow;

use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowResult;

interface ExecutableWorkflow
{
    /**
     * @throws WorkflowException
     */
    public function executeWorkflow(
        ?WorkflowContainer $workflowContainer = null,
        bool $throwOnFailure = true
    ): WorkflowResult;
}