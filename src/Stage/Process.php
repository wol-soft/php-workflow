<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\State\WorkflowState;

class Process extends Stage
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

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_PROCESS);

        try {
            $this->wrapStepExecution($this->process, $workflowState);
        } catch (Exception $exception) {
            $workflowState->setProcessException($exception);
        }

        $this->next($workflowState);
    }
}
