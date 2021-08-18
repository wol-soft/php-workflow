<?php

declare(strict_types=1);

namespace PHPWorkflow\Middleware;

use Exception;
use PHPWorkflow\WorkflowControl;

class ProfileStep
{
    public function __invoke(callable $next, WorkflowControl $control)
    {
        $start = microtime(true);
        $profile = fn () => $control->attachStepInfo(
            "Step execution time: " . number_format(1000 * (microtime(true) - $start), 5) . 'ms',
        );

        try {
            $result = $next();
            $profile();
        } catch (Exception $exception) {
            $profile();
            throw $exception;
        }

        return $result;
    }
}
