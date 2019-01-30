<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Loop;

use Async\Loop\Loop;
use Async\Loop\ProcessInterface;

/**
 * @internal 
 */
class Processor
{
    private $processes = array();
    private $timeout = 300;
    private $sleepTime = 25000;
    private $timedOutCallback = null;
    private $finishCallback = null;
    private $failCallback = null;
    private static $pcntl = false;
    private static $loop = null;
	
    public function __construct(callable $timedOutCallback = null, 
        callable $finishCallback = null, 
        callable $failCallback = null)
    {        
        self::$pcntl = $this->isPcntl();
        self::$loop = Loop::getInstance();
        $this->init($timedOutCallback,  $finishCallback,  $failCallback);
		
		if (self::$pcntl)
            $this->registerListener();
    }

    public function add(ProcessInterface $process)
    {
        $this->processes[$process->getPid()] = $process;		
    }

    public function remove(ProcessInterface $process)
    {
        unset($this->processes[$process->getPid()]);
    }

    public function stop(ProcessInterface $process)
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
                if ($process->getExecutionTime() > $this->timeout) {
                    $this->remove($process);
					$markTimedOuted = $this->timedOutCallback;
                    $markTimedOuted($process);
                } 
                
                if (! self::$pcntl) {
					if ($process->isSuccessful()) {
                        $this->remove($process);
						$markFinished = $this->finishCallback;		
						$markFinished($process);
					} elseif (! $process->isRunning() && $process->isTerminated()) {
                        $this->remove($process);
						$markFailed = $this->failCallback;
						$markFailed($process);
					}
                }                
			}
        }
    }
	
    public function sleepTime(int $sleepTime)
    {
        $this->sleepTime = $sleepTime;
    }

    public function timeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function timingOut(): int 
    {
        return (int) $this->timeout;
    }
	
    public function sleepingTime(): int
    {
        return $this->sleepTime;
    }
	
    public function init(callable $timedOutCallback = null, 
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
	
    public function processCount(): int
    {
        return count($this->processes);
    }
	
    public static function isPcntl(): bool
    {
        self::$pcntl = function_exists('pcntl_async_signals')
            && function_exists('posix_kill');
		
        return self::$pcntl;
    }
	
    protected function registerListener()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGCHLD, function ($signo, $status) {
            while (true) {
                $pid = pcntl_waitpid(-1, $processState, WNOHANG | WUNTRACED);

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
                    $markFinished($process);	

                    continue;
                }
				
                $this->remove($process);				
                $markFailed = $this->failCallback;
                $markFailed($process);
            }
        });
    }
	
    private function callSuccess(ProcessInterface $process)
    {
        $this->remove($process);
        $process->triggerSuccess();
    }

    private function callError(ProcessInterface $process)
    {
        $this->remove($process);
        $process->triggerError();
    }

    private function callTimeout(ProcessInterface $process)
    {
        $this->remove($process);
        $process->triggerTimeout();
    }
}
