<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Next;

use PHPWorkflow\Step\After;

trait AllowNextAfter
{
    public function after(callable $after): After
    {
        return $this->next = (new After($this->workflow))->after($after);
    }
}
