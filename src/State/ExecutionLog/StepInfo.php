<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

class StepInfo
{
    public const NESTED_WORKFLOW = 'STEP_NESTED_WORKFLOW';
    public const LOOP = 'STEP_LOOP';

    private string $info;
    private array $context;

    public function __construct(string $info, array $context)
    {
        $this->info = $info;
        $this->context = $context;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
