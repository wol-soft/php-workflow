<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Step\WorkflowStep;

class Process extends Stage
{
    private WorkflowStep $process;

    public function process(WorkflowStep $process): Process
    {
        if ($this->process) {
            throw new Exception('Process already attached');
        }

        $this->process = $process;
        $this->next = new AfterProcess($this->workflow);

        return $this;
    }

    public function getAfterProcess(): AfterProcess
    {
        return $this->next;
    }

    protected function run(WorkflowState $workflowState): ?Stage
    {
        $workflowState->setStage(WorkflowState::STAGE_PROCESS);

        try {
            $this->wrapStepExecution($this->process, $workflowState);
        } catch (Exception $exception) {
            $workflowState->setProcessException($exception);
        }

        return $this->next;
    }
}
