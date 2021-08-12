<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\State\WorkflowState;

class Process extends Stage
{
    private $process;

    public function process(string $description, callable $process): Process
    {
        if ($this->process) {
            throw new Exception('Process already attached');
        }

        $this->process = [$description, $process];
        $this->next = new AfterProcess($this->workflow);

        return $this;
    }

    public function getAfterProcess(): AfterProcess
    {
        return $this->next;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_PROCESS);

        try {
            [$description, $process] = $this->process;
            $this->wrapStepExecution($description, $process, $workflowState);
        } catch (Exception $exception) {
            $workflowState->setProcessException($exception);
        }

        $this->next($workflowState);
    }
}
