<?php

declare(strict_types=1);

namespace PHPWorkflow\Stage\Next;

use PHPWorkflow\Stage\Validation;

trait AllowNextValidator
{
    public function validate(callable $validator, bool $hardValidator = false): Validation
    {
        return $this->next = (new Validation($this->workflow))->validate($validator, $hardValidator);
    }
}
