<?php

declare(strict_types=1);

namespace PHPWorkflow\Step;

use PHPWorkflow\Exception\WorkflowException;
use PHPWorkflow\ExecutableWorkflow;
use PHPWorkflow\State\WorkflowContainer;
use PHPWorkflow\WorkflowControl;

class NestedWorkflow implements WorkflowStep
{
    private ExecutableWorkflow $nestedWorkflow;

    public function __construct(ExecutableWorkflow $nestedWorkflow)
    {
        $this->nestedWorkflow = $nestedWorkflow;
    }

    public function getDescription(): string
    {
        return "Execute nested workflow";
    }

    public function run(WorkflowControl $control, WorkflowContainer $container)
    {
        try {
            $workflowResult = $this->nestedWorkflow->executeWorkflow(
                $container,
                // TODO: array unpacking via named arguments when dropping PHP7 support
                $container->get('__internalExecutionConfiguration')['throwOnFailure'],
            );
        } catch (WorkflowException $exception) {
            $workflowResult = $exception->getWorkflowResult();
        }

        $control->attachStepInfo(
            str_replace(
                "\n      \n",
                "\n\n",
                str_replace("\n", "\n      ", $workflowResult->debug()),
            ),
        );

        if ($workflowResult->getWarnings()) {
            $warnings = count($workflowResult->getWarnings(), COUNT_RECURSIVE) - count($workflowResult->getWarnings());
            $control->warning(
                sprintf(
                    "Nested workflow '%s' emitted %s warning%s",
                    $workflowResult->getWorkflowName(),
                    $warnings,
                    $warnings > 1 ? 's' : '',
                ),
            );
        }

        if (!$workflowResult->success()) {
            $control->failStep("Nested workflow '{$workflowResult->getWorkflowName()}' failed");
        }
    }
}
