<?php

namespace Async\Loop;

interface ProcessorInterface
{
    public function getId(): int;

    /**
     * Get process PID.
     *
     * @return int  process id
     */
    public function getPid(): ?int;
	
    /**
     * Start the process
     * 
     * @return ProcessInterface
     */
    public function start();

    public function then(callable $callback);

    public function catch(callable $callback);

    public function timeout(callable $callback);

    /**
     * Stops the process.
     *
     * @return ProcessInterface
     */
    public function stop();

    /**
     * Determines if the process is still running.
     *
     * @return bool
     */
    public function isRunning();

    /**
     * Checks if the process ended successfully.
     *
     * @return bool true if the process ended successfully, false otherwise
     */
    public function isSuccessful();

    /**
     * Checks if the process is terminated.
     *
     * @return bool true if process is terminated, false otherwise
     */
    public function isTerminated();
	
    /**
     * Returns the current output of the process (STDOUT).
     *
     * @return string The process output
     */
    public function getOutput();
		
    /**
     * Returns the current error output of the process (STDERR).
     *
     * @return string The process error output
     */   
    public function getErrorOutput();

    public function triggerSuccess();

    public function triggerError();

    public function triggerTimeout();

    public function getExecutionTime(): float;
}
