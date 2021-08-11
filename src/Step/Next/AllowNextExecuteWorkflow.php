<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

trait AllowNextExecuteWorkflow
{
    public function executeWorkflow(): void
    {
        $this->workflow->run();
    }
}
