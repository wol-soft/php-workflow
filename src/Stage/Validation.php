<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage;

use Exception;
use PHPWorkflow\Exception\WorkflowValidationException;
use PHPWorkflow\State\WorkflowState;
use PHPWorkflow\Stage\Next\AllowNextBefore;
use PHPWorkflow\Stage\Next\AllowNextProcess;
use PHPWorkflow\Validator;

class Validation extends Stage
{
    use
        AllowNextBefore,
        AllowNextProcess;

    /** @var Validator[] */
    private array $validators = [];

    public function validate(string $description, callable $validator, bool $hardValidator = false): self
    {
        $this->validators[] = new Validator($description, $validator, $hardValidator);
        return $this;
    }

    protected function run(WorkflowState $workflowState): void
    {
        $workflowState->setStage(WorkflowState::STAGE_VALIDATION);

        // make sure hard validators are executed first
        usort($this->validators, function (Validator $validator, Validator $comparedValidator): int {
            if ($validator->isHardValidator() xor $comparedValidator->isHardValidator()) {
                return $validator->isHardValidator() && !$comparedValidator->isHardValidator() ? -1 : 1;
            }
            return 0;
        });

        foreach ($this->validators as $validator) {
            if ($validator->isHardValidator()) {
                $this->wrapStepExecution($validator->getDescription(), $validator->getValidator(), $workflowState);
            } else {
                $validationErrors = [];

                try {
                    $this->wrapStepExecution($validator->getDescription(), $validator->getValidator(), $workflowState);
                } catch (Exception $exception) {
                    $validationErrors[] = $exception;
                }

                if ($validationErrors) {
                    throw new WorkflowValidationException($validationErrors);
                }
            }
        }

        $this->next($workflowState);
    }
}
