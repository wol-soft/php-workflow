<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use Exception;

class Process extends Step
{
    /** @var callable */
    private $process;

    public function process(callable $process): AfterProcess
    {
        if ($this->process) {
            throw new Exception('Process already attached');
        }

        $this->process = $process;

        return $this->next = new AfterProcess($this->workflow);
    }

    protected function run(): void
    {
        foreach ($this->process as $process) {
            ($process)();
        }

        $this->next();
    }
}
