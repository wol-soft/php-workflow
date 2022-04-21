<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

class WorkflowContainer
{
    protected array $items = [];

    public function get(string $key)
    {
        return $this->items[$key] ?? null;
    }

    public function set(string $key, $value): self
    {
        $this->items[$key] = $value;
        return $this;
    }

    public function unset(string $key): self
    {
        unset($this->items[$key]);
        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}
