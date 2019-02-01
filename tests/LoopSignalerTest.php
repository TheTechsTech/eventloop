<?php

namespace Async\Tests;

use Async\Loop\Loop;
use PHPUnit\Framework\TestCase;

class LoopSignalerTest extends TestCase 
{
    protected $loop;

	protected function setUp()
    {
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
		Loop::clearInstance();
        $this->loop = Loop::getInstance();
        $this->loop->initSignals();
    }

    private function assertRunFasterThan($maxInterval)
    {
        $start = microtime(true);
        $this->loop->run();
        $end = microtime(true);
        $interval = $end - $start;
        $this->assertLessThan($maxInterval, $interval);
    }

    private function assertRunSlowerThan($minInterval)
    {
        $start = microtime(true);
        $this->loop->run();
        $end = microtime(true);
        $interval = $end - $start;
        $this->assertLessThan($interval, $minInterval);
    }

    public function testRemoveSignalNotRegisteredIsNoOp()
    {
        $loop = $this->loop;
        $loop->removeSignal(SIGINT, function () { });
        $this->assertTrue(true);
    }
	
    public function testSignalMultipleUsagesForTheSameListener()
    {
        $loop = $this->loop;
        $funcCallCount = 0;
        $func = function () use (&$funcCallCount) {
            $funcCallCount++;
        };
        $loop->addTimeout(function () {}, 1);
        $loop->addSignal(SIGUSR1, $func);
        $loop->addSignal(SIGUSR1, $func);
        $loop->addTimeout(function () {
            posix_kill(posix_getpid(), SIGUSR1);
        }, 0.4);
        $loop->addTimeout(function () use (&$func, $loop) {
            $loop->removeSignal(SIGUSR1, $func);
        }, 0.9);
        $loop->run();
        $this->assertSame(1, $funcCallCount);
    }
	
    public function testSignalsKeepTheLoopRunning()
    {
        $loop = $this->loop;
        $function = function () {};
        $loop->addSignal(SIGUSR1, $function);
        $loop->addTimeout(function () use ($function, $loop) {
            $loop->removeSignal(SIGUSR1, $function);
            $loop->stop();
        }, 1.5);
        $loop->run();
        $this->assertRunSlowerThan(1.5);
    }
	
    public function testSignalsKeepTheLoopRunningAndRemovingItStopsTheLoop()
    {
        $loop = $this->loop;
        $function = function () {};
        $loop->addSignal(SIGUSR1, $function);
        $loop->addTimeout(function () use ($function, $loop) {
            $loop->removeSignal(SIGUSR1, $function);
        }, 1.5);
        $loop->run();
        $this->assertRunFasterThan(1.6);
    }
}
