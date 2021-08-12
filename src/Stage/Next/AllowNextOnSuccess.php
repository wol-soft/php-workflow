<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\OnSuccess;

trait AllowNextOnSuccess
{
    public function onSuccess(string $description, callable $onSuccess): OnSuccess
    {
        return $this->next = (new OnSuccess($this->workflow))->onSuccess($description, $onSuccess);
    }
}
