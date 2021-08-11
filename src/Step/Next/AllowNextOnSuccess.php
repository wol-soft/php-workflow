<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

use PHPWorkflow\Step\OnSuccess;

trait AllowNextOnSuccess
{
    public function onSuccess(callable $onSuccess): OnSuccess
    {
        return $this->next = (new OnSuccess($this->workflow))->onSuccess($onSuccess);
    }
}
