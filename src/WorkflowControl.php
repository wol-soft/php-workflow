<?php

declare(strict_types=1);

namespace PHPWorkflow;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\FailStepException;
use PHPWorkflow\Exception\WorkflowControl\FailWorkflowException;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\State\ExecutionLog\ExecutionLog;

class WorkflowControl
{
    private ExecutionLog $executionLog;

    public function __construct(ExecutionLog $executionLog)
    {
        $this->executionLog = $executionLog;
    }

    /**
     * Mark the current step as skipped.
     * Use this if you detect, that the step execution is not necessary
     * (e.g. disabled by config, no entity to process, ...)
     *
     * @param string $reason
     */
    public function skipStep(string $reason): void
    {
        throw new SkipStepException($reason);
    }

    /**
     * Mark the current step as failed.
     * A failed step before and during the processing of a workflow leads to a failed workflow.
     *
     * @param string $reason
     */
    public function failStep(string $reason): void
    {
        throw new FailStepException($reason);
    }

    /**
     * Mark the workflow as failed.
     * If the workflow is failed after the process stage has been executed it's handled like a failed step.
     *
     * @param string $reason
     */
    public function failWorkflow(string $reason): void
    {
        throw new FailWorkflowException($reason);
    }

    /**
     * Skip the further workflow execution (e.g. if you detect it's not necessary to process the workflow).
     * If the workflow is skipped after the process stage has been executed it's handled like a skipped step.
     *
     * @param string $reason
     */
    public function skipWorkflow(string $reason): void
    {
        throw new SkipWorkflowException($reason);
    }

    /**
     * Attach any additional debug info to your current step.
     * Info will be shown in the workflow debug log.
     *
     * @param string $info
     */
    public function attachStepInfo(string $info): void
    {
        $this->executionLog->attachStepInfo($info);
    }

    /**
     * Add a warning to the workflow.
     * All warnings will be collected and shown in the workflow debug log.
     *
     * @param string $message
     * @param Exception|null $exception An exception causing the warning
     *                                  (if provided exception information will be added to the warning)
     */
    public function warning(string $message, ?Exception $exception = null): void
    {
        if ($exception) {
            $exceptionClass = get_class($exception);
            $message .= sprintf(
                " (%s%s in %s::%s)",
                strstr($exceptionClass, '\\') ? trim(strrchr($exceptionClass, '\\'), '\\') : $exceptionClass,
                ($exception->getMessage() ? ": {$exception->getMessage()}" : ''),
                $exception->getFile(),
                $exception->getLine(),
            );
        }

        $this->executionLog->addWarning($message);
    }
}
