<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

class Step
{
    private string $step;
    private string $state;
    private ?string $reason;
    private array $stepInfo;
    private int $warnings;

    public function __construct(string $step, string $state, ?string $reason, array $stepInfo, int $warnings)
    {
        $this->step = $step;
        $this->state = $state;
        $this->reason = $reason;
        $this->stepInfo = $stepInfo;
        $this->warnings = $warnings;
    }

    public function __toString(): string
    {
        $stepLog = "{$this->step}: {$this->state}" .
            ($this->reason ? " ({$this->reason})" : '') .
            ($this->warnings ? " ({$this->warnings} warning" . ($this->warnings > 1 ? 's' : '') . ")" : '');

        foreach ($this->stepInfo as $info) {
            $stepLog .= "\n    - $info";
        }

        return $stepLog;
    }
}
