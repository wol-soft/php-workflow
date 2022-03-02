<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog\OutputFormat;

use PHPWorkflow\State\ExecutionLog\Step;

interface OutputFormat
{
    /**
     * @param string $workflowName
     * @param Step[][] $steps Contains a list of the executed steps, grouped by the executed stages
     *
     * @return mixed
     */
    public function format(string $workflowName, array $steps);
}