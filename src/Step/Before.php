<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\Step\Next\AllowNextProcess;

class Before extends Step
{
    use AllowNextProcess;

    /** @var callable[] */
    private array $before = [];

    public function before(callable $before): self
    {
        $this->before[] = $before;
        return $this;
    }

    protected function run(): void
    {
        foreach ($this->before as $before) {
            ($before)();
        }

        $this->next();
    }
}
