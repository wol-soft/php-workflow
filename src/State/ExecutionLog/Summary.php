<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog;

class Summary implements Describable
{
    private string $description;

    public function __construct(string $description)
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
