<?php

declare(strict_types=1);

namespace PHPWorkflow\Tests;

use PHPWorkflow\State\ExecutionLog\OutputFormat\OutputFormat;
use PHPWorkflow\State\WorkflowResult;

trait WorkflowTestTrait
{
    protected function assertDebugLog(string $expected, WorkflowResult $result, ?OutputFormat $formatter = null): void
    {
        $this->assertSame(
            $expected,
            preg_replace(
                [
                    '#[\w\\\\]+@anonymous[^)]+#',
                    '/[\d.]+ms/',
                ],
                [
                    'anonClass',
                    '*',
                ],
                $result->debug($formatter),
            ),
        );
    }

    protected function expectFailAtStep(string $step, WorkflowResult $result): void
    {
        $this->assertFalse($result->success(), 'Expected failing workflow');
        $this->assertSame($step, get_class($result->getLastStep()), 'Workflow failed at a wrong step');
    }

    protected function expectSkipAtStep(string $step, WorkflowResult $result): void
    {
        $this->assertTrue($result->success(), 'Expected successful workflow');
        $this->assertSame($step, get_class($result->getLastStep()), 'Workflow skipped at a wrong step');
    }
}
