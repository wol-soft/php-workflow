<?php

declare(strict_types=1);

namespace PHPWorkflow\State\ExecutionLog\OutputFormat;

use Exception;

class WorkflowGraph implements OutputFormat
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function format(string $workflowName, array $steps): string
    {
        $this->generateImageFromScript(
            (new GraphViz())->format($workflowName, $steps),
            $filePath = $this->path . DIRECTORY_SEPARATOR . $workflowName . '_' . uniqid() . '.svg',
        );

        return $filePath;
    }

    private function generateImageFromScript(string $script, string $file)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'graphviz');
        if ($tmp === false) {
            throw new Exception('Unable to get temporary file name for graphviz script');
        }

        $ret = file_put_contents($tmp, $script, LOCK_EX);
        if ($ret === false) {
            throw new Exception('Unable to write graphviz script to temporary file');
        }

        $ret = 0;

        system('dot -T svg ' . escapeshellarg($tmp) . ' -o ' . escapeshellarg($file), $ret);
        if ($ret !== 0) {
            throw new Exception('Unable to invoke "dot" to create image file (code ' . $ret . ')');
        }

        unlink($tmp);
    }
}
