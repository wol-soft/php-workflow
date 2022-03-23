<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\ExecutableWorkflow;
use PHPWorkflow\State\ExecutionLog\StepInfo;
use PHPWorkflow\State\NestedContainer;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\State\WorkflowResult;
use PHPWorkflow\WorkflowControl;

class NestedWorkflow implements WorkflowStep
{
    private ExecutableWorkflow $nestedWorkflow;
    private ?WorkflowContainer $container;
    private WorkflowResult $workflowResult;

    public function __construct(ExecutableWorkflow $nestedWorkflow, ?WorkflowContainer $container = null)
    {
        $this->nestedWorkflow = $nestedWorkflow;
        $this->container = $container;
    }

    public function getDescription(): string
    {
        return "Execute nested workflow";
    }

    public function run(WorkflowControl $control, WorkflowContainer $container): void
    {
        try {
            $this->workflowResult = $this->nestedWorkflow->executeWorkflow(
                new NestedContainer($container, $this->container),
                // TODO: array unpacking via named arguments when dropping PHP7 support
                $container->get('__internalExecutionConfiguration')['throwOnFailure'],
            );
        } catch (WorkflowException $exception) {
            $this->workflowResult = $exception->getWorkflowResult();
        }

        $control->attachStepInfo(StepInfo::NESTED_WORKFLOW, ['result' => $this->workflowResult]);

        if ($this->workflowResult->getWarnings()) {
            $warnings = count($this->workflowResult->getWarnings(), COUNT_RECURSIVE) -
                count($this->workflowResult->getWarnings());

            $control->warning(
                sprintf(
                    "Nested workflow '%s' emitted %s warning%s",
                    $this->workflowResult->getWorkflowName(),
                    $warnings,
                    $warnings > 1 ? 's' : '',
                ),
            );
        }

        if (!$this->workflowResult->success()) {
            $control->failStep("Nested workflow '{$this->workflowResult->getWorkflowName()}' failed");
        }
    }

    public function getNestedWorkflowResult(): WorkflowResult
    {
        return $this->workflowResult;
    }
}
