<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\AfterProcess;
use PHPWorkflow\Stage\Process;

trait AllowNextProcess
{
    public function process(callable $process): AfterProcess
    {
        return $this->next = (new Process($this->workflow))->process($process);
    }
}
