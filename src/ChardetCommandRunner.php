<?php

namespace Yupmin\PHPChardet;

use Symfony\Component\Process\Process;

class ChardetCommandRunner
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var string
     */
    protected $command = 'chardet';

    /**
     * @var array
     */
    protected $arguments = array();

    /**
     * @param string $filePath
     * @param array|null $arguments
     * @param Process|null $process
     */
    public function __construct($filePath, array $arguments = null, $process = null)
    {
        $this->filePath = $filePath;

        if ($arguments !== null) {
            $this->arguments = $arguments;
        }

        if ($process === null) {
            $command = array_merge(array($this->command), $this->arguments, array($this->filePath));
            $process = new Process($command, null, null, null, 60);
        }

        $this->process = $process;
    }

    /**
     * @return string
     */
    public function run()
    {
        $this->process->run();

        if (!$this->process->isSuccessful()) {
            throw new \RuntimeException($this->process->getErrorOutput());
        }

        return $this->process->getOutput();
    }
}
