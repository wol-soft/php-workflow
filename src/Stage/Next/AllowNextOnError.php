<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\OnError;

trait AllowNextOnError
{
    public function onError(string $description, callable $onError): OnError
    {
        return $this->next = (new OnError($this->workflow))->onError($description, $onError);
    }
}
