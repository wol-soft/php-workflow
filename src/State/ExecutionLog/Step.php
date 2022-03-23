<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

class Step
{
    private Describable $step;
    private string $state;
    private ?string $reason;
    private array $stepInfo;
    private int $warnings;

    public function __construct(Describable $step, string $state, ?string $reason, array $stepInfo, int $warnings)
    {
        $this->step = $step;
        $this->state = $state;
        $this->reason = $reason;
        $this->stepInfo = $stepInfo;
        $this->warnings = $warnings;
    }

    public function getDescription(): string
    {
        return $this->step->getDescription();
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @return StepInfo[]
     */
    public function getStepInfo(): array
    {
        return $this->stepInfo;
    }

    public function getWarnings(): int
    {
        return $this->warnings;
    }
}
