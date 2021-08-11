<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

use PHPWorkflow\Step\OnError;

trait AllowNextOnError
{
    public function onError(callable $onError): OnError
    {
        return $this->next = (new OnError($this->workflow))->onError($onError);
    }
}
