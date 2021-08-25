<?php

namespace PHPWorkflow;

use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowResult;

interface ExecutableWorkflow
{
    public function executeWorkflow(
        WorkflowContainer $workflowContainer = null,
        bool $throwOnFailure = true,
        bool $logErrors = true
    ): WorkflowResult;
}