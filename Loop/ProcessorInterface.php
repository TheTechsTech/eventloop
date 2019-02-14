<?php

namespace Async\Loop;

interface ProcessorInterface
{
    /**
     * Get process PID.
     *
     * @return int  process id
     */
    public function getPid(): ?int;

    /**
     * Stops the process.
     */
    public function stop();
	
    /**
     * Determines if the process has timed out, and only if an timeout has been set.
     *
     * @return bool
     */
    public function isTimedOut();
	
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
	
    public function triggerSuccess();

    public function triggerError();

    public function triggerTimeout();
}
