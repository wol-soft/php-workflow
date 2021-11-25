<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\Exception\WorkflowControl\SkipStepException;
use PHPWorkflow\Exception\WorkflowControl\SkipWorkflowException;
use PHPWorkflow\Exception\WorkflowValidationException;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextBefore;
use PHPWorkflow\Stage\Next\AllowNextProcess;
use PHPWorkflow\Step\WorkflowStep;
use PHPWorkflow\Validator;

class Validate extends Stage
{
    use
        AllowNextBefore,
        AllowNextProcess;

    /** @var Validator[] */
    private array $validators = [];

    public function validate(WorkflowStep $step, bool $hardValidator = false): self
    {
        $this->validators[] = new Validator($step, $hardValidator);
        return $this;
    }

    protected function runStage(WorkflowState $workflowState): ?Stage
    {
        $workflowState->setStage(WorkflowState::STAGE_VALIDATE);

        // make sure hard validators are executed first
        usort($this->validators, function (Validator $validator, Validator $comparedValidator): int {
            if ($validator->isHardValidator() xor $comparedValidator->isHardValidator()) {
                return $validator->isHardValidator() && !$comparedValidator->isHardValidator() ? -1 : 1;
            }
            return 0;
        });

        $validationErrors = [];
        foreach ($this->validators as $validator) {
            $workflowState->setStep($validator->getStep());

            if ($validator->isHardValidator()) {
                $this->wrapStepExecution($validator->getStep(), $workflowState);
            } else {
                try {
                    $this->wrapStepExecution($validator->getStep(), $workflowState);
                } catch (SkipWorkflowException $exception) {
                    throw $exception;
                } catch (Exception $exception) {
                    $validationErrors[] = $exception;
                }
            }
        }

        if ($validationErrors) {
            throw new WorkflowValidationException($validationErrors);
        }

        return $this->nextStage;
    }
}
