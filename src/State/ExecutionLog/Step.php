<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

class Step
{
    private string $step;
    private string $state;
    private ?string $reason;

    public function __construct(string $step, string $state, ?string $reason)
    {
        $this->step = $step;
        $this->state = $state;
        $this->reason = $reason;
    }

    public function __toString(): string
    {
        return "{$this->step}: {$this->state}" . ($this->reason ? " ({$this->reason})" : '');
    }
}
