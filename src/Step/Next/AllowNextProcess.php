<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

use PHPWorkflow\Step\AfterProcess;
use PHPWorkflow\Step\Process;

trait AllowNextProcess
{
    public function process(callable $process): AfterProcess
    {
        return $this->next = (new Process($this->workflow))->process($process);
    }
}
