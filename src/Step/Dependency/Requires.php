<?php

declare(strict_types=1);

namespace PHPWorkflow\Step\Dependency;

use Attribute;
use PHPWorkflow\Exception\WorkflowStepDependencyNotFulfilledException;
use PHPWorkflow\State\WorkflowContainer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Requires implements StepDependencyInterface
{
    public function __construct(private string $key, private ?string $type = null) {}

    public function check(WorkflowContainer $container): void
    {
        if (!$container->has($this->key)) {
            throw new WorkflowStepDependencyNotFulfilledException("Missing '$this->key' in container");
        }

        $value = $container->get($this->key);

        if ($this->type === null || (str_starts_with($this->type, '?') && $value === null)) {
            return;
        }

        $type = str_replace('?', '', $this->type);

        if (preg_match('/^(string|bool|int|float|object|array|iterable|scalar)$/', $type, $matches) === 1) {
            $checkMethod = 'is_' . $matches[1];

            if ($checkMethod($value)) {
                return;
            }
        } elseif (class_exists($type) && ($value instanceof $type)) {
            return;
        }

        throw new WorkflowStepDependencyNotFulfilledException(
            sprintf(
                "Value for '%s' has an invalid type. Expected %s, got %s",
                $this->key,
                $this->type,
                gettype($value) . (is_object($value) ? sprintf(' (%s)', $value::class) : ''),
            ),
        );
    }
}
