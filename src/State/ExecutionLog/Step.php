<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

class Step
{
    private string $step;
    private string $state;
    private ?string $reason;
    private array $stepInfo;

    public function __construct(string $step, string $state, ?string $reason, array $stepInfo)
    {
        $this->step = $step;
        $this->state = $state;
        $this->reason = $reason;
        $this->stepInfo = $stepInfo;
    }

    public function __toString(): string
    {
        $stepLog = "{$this->step}: {$this->state}" . ($this->reason ? " ({$this->reason})" : '');

        foreach ($this->stepInfo as $info) {
            $stepLog .= "\n    - $info";
        }

        return $stepLog;
    }
}
