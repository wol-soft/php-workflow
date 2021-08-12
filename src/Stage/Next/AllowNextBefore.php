<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Before;

trait AllowNextBefore
{
    public function before(callable $before): Before
    {
        return $this->next = (new Before($this->workflow))->before($before);
    }
}
