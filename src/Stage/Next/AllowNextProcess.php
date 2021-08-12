<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\AfterProcess;
use PHPWorkflow\Stage\Process;

trait AllowNextProcess
{
    public function process(string $description, callable $process): AfterProcess
    {
        $this->next = (new Process($this->workflow))->process($description, $process);

        return $this->next->getAfterProcess();
    }
}
