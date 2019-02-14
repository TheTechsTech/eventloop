<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Loop;

use Async\Loop\Loop;
use Async\Loop\ProcessorInterface;

/**
 * @internal 
 */
class Processor
{
    private $processes = array();
    private $sleepTime = 50000;
    private $timedOutCallback = null;
    private $finishCallback = null;
    private $failCallback = null;
    private static $pcntl = false;
    private static $loop = null;
	
    public function __construct(
        callable $timedOutCallback = null, 
        callable $finishCallback = null, 
        callable $failCallback = null)
    {
        self::$loop = Loop::getInstance();
        $this->init($timedOutCallback,  $finishCallback,  $failCallback);
		
		if ($this->isPcntl())
            $this->registerProcessor();
    }

    public function add(ProcessorInterface $process)
    {
        $this->processes[$process->getPid()] = $process;		
    }

    public function remove(ProcessorInterface $process)
    {
        unset($this->processes[$process->getPid()]);
    }

    public function stop(ProcessorInterface $process)
    {
        $this->remove($process);
        $process->stop();
    }

    public function stopAll()
    {
        if ($this->processes) {
            foreach ($this->processes as $process) {
                $this->stop($process);
			}
        }
    }
	
    public function processing()
    {
        if ($this->processes) {
            foreach ($this->processes as $process) {                
                if ($process->isTimedOut()) {
                    $this->remove($process);
					$markTimedOuted = $this->timedOutCallback;

                    if (! method_exists($markTimedOuted, 'callTimeout'))
                        self::$loop->addTask(\awaitAble($markTimedOuted, $process));
                    else
                        self::$loop->addTick(function () use ($markTimedOuted, $process) {
                            $markTimedOuted($process);
                        });
                } 
                
                if (! self::$pcntl) {
					if ($process->isRunning()) {
                        continue;
					} elseif ($process->isSuccessful()) {
                        $this->remove($process);
						$markFinished = $this->finishCallback;

                        if (! method_exists($markFinished, 'callSuccess'))
                            self::$loop->addTask(\awaitAble($markFinished, $process));
                        else
                            self::$loop->addTick(function () use ($markFinished, $process) {
                                $markFinished($process);
                            });
                    } elseif ($process->isTerminated()) {
                        $this->remove($process);
                        $markFailed = $this->failCallback;

                        if (! method_exists($markFailed, 'callError'))
                            self::$loop->addTask(\awaitAble($markFailed, $process));
                        else
                            self::$loop->addTick(function () use ($markFailed, $process) {
                                $markFailed($process);
                            });
                    } 
                }
			}
        }
    }
	
    public function sleepTime(int $sleepTime)
    {
        $this->sleepTime = $sleepTime;
    }
	
    public function sleepingTime(): int
    {
        return $this->sleepTime;
    }
	
    public function init(
        callable $timedOutCallback = null, 
        callable $finishCallback = null, 
        callable $failCallback = null)
    {
        $this->timedOutCallback = empty($timedOutCallback) ? [$this, 'callTimeout'] : $timedOutCallback;
        $this->finishCallback = empty($finishCallback) ? [$this, 'callSuccess'] : $finishCallback;
        $this->failCallback = empty($failCallback) ? [$this, 'callError'] : $failCallback;
    }	
	
    public function isEmpty(): bool
    {
        return empty($this->processes);
    }
	
    public function count(): int
    {
        return \count($this->processes);
    }
	
    public static function isPcntl(): bool
    {
        self::$pcntl = Loop::isPcntl();
		
        return self::$pcntl;
    }
	
    protected function registerProcessor()
    {
        \pcntl_async_signals(true);

        \pcntl_signal(\SIGCHLD, function ($signo, $status) {
            while (true) {
                $pid = \pcntl_waitpid(-1, $processState, \WNOHANG | \WUNTRACED);

                if ($pid <= 0) {
                    break;
                }

                $process = $this->processes[$pid] ?? null;

                if (! $process) {
                    continue;
                }

                if ($status['status'] === 0) {
                    $this->remove($process);
                    $markFinished = $this->finishCallback;

                    if (! method_exists($markFinished, 'callSuccess'))
                        self::$loop->addTask(\awaitAble($markFinished, $process));
                    else
                        self::$loop->addTick(function () use ($markFinished, $process) {
                            $markFinished($process);
                        });

                    continue;
                }
				
                $this->remove($process);				
                $markFailed = $this->failCallback;

                if (! method_exists($markFailed, 'callError'))
                    self::$loop->addTask(\awaitAble($markFailed, $process));
                else
                    self::$loop->addTick(function () use ($markFailed, $process) {
                        $markFailed($process);
                    });
            }
        });
    }
	
    private function callSuccess(ProcessorInterface $process)
    {
		$process->triggerSuccess();
    }

    private function callError(ProcessorInterface $process)
    {
		$process->triggerError();
    }

    private function callTimeout(ProcessorInterface $process)
    {
		$process->triggerTimeout();
    }
}
