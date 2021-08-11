<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\Step\Next\AllowNextAfter;
use PHPWorkflow\Step\Next\AllowNextOnError;
use PHPWorkflow\Step\Next\AllowNextExecuteWorkflow;

class OnSuccess extends Step
{
    use
        AllowNextOnError,
        AllowNextAfter,
        AllowNextExecuteWorkflow;

    /** @var callable[] */
    private array $onSuccess = [];

    public function onSuccess(callable $onSuccess): self
    {
        $this->onSuccess[] = $onSuccess;
        return $this;
    }

    protected function run(): void
    {
        foreach ($this->onSuccess as $onSuccess) {
            ($onSuccess)();
        }

        $this->next();
    }
}
