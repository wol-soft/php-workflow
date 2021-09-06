<?php

declare(strict_types=1);

namespace PHPWorkflow\State;

class NestedContainer extends WorkflowContainer
{
    private WorkflowContainer $parentContainer;
    private ?WorkflowContainer $container;

    public function __construct(WorkflowContainer $parentContainer, ?WorkflowContainer $container)
    {
        $this->parentContainer = $parentContainer;
        $this->container = $container;
    }

    public function get(string $key)
    {
        if (!$this->container) {
            return $this->parentContainer->get($key);
        }

        return $this->container->get($key) ?? $this->parentContainer->get($key);
    }

    public function set(string $key, $value): WorkflowContainer
    {
        if ($this->container) {
            $this->container->set($key, $value);
        }

        $this->parentContainer->set($key, $value);

        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        if ($this->container && method_exists($this->container, $name)) {
            return $this->container->{$name}(...$arguments);
        }

        // don't check if the method exists to trigger an error if the method neither exists in $container nor in
        // $parentContainer
        return $this->parentContainer->{$name}(...$arguments);
    }
}
