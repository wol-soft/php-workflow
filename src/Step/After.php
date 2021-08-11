<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\Step\Next\AllowNextExecuteWorkflow;

class After extends Step
{
    use AllowNextExecuteWorkflow;

    /** @var callable[] */
    private array $after = [];

    public function after(callable $after): self
    {
        $this->after[] = $after;
        return $this;
    }

    protected function run(): void
    {
        foreach ($this->after as $after) {
            ($after)();
        }

        $this->next();
    }
}
