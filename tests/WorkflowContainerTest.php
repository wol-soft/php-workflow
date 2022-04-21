<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use PHPUnit\Framework\TestCase;
use PHPWorkflow\State\WorkflowContainer;

class WorkflowContainerTest extends TestCase
{
    public function testWorkflowContainer(): void
    {
        $container = new WorkflowContainer();

        $this->assertFalse($container->has('non existing key'));
        $this->assertNull($container->get('non existing key'));

        $container->set('key', 42);
        $this->assertTrue($container->has('key'));
        $this->assertSame(42, $container->get('key'));

        $container->set('key', 'Updated');
        $this->assertTrue($container->has('key'));
        $this->assertSame('Updated', $container->get('key'));

        $container->unset('key');
        $this->assertFalse($container->has('key'));
        $this->assertNull($container->get('key'));
    }
}
