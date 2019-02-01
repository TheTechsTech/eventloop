<?php

namespace Async\Tests;

use Async\Loop\Loop;
use PHPUnit\Framework\TestCase;

class LoopSignalerTest extends TestCase 
{
	protected function setUp()
    {
        //$this->markTestSkipped('Pause these tests for now.');
		Loop::clearInstance();
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
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $loop = new Loop();
        $loop->removeSignal(SIGINT, function () { });
        $this->assertTrue(true);
    }
	
    public function testSignal()
    {
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $loop = new Loop();
        $called = false;
        $calledShouldNot = true;
        $timer = $loop->setInterval(function () {}, 1);
        $loop->addSignal(SIGUSR2, $func2 = function () use (&$calledShouldNot) {
            $calledShouldNot = false;
        });
        $loop->addSignal(SIGUSR1, $func1 = function () use (&$func1, &$func2, &$called, $timer, $loop) {
            $called = true;
            $loop->removeSignal(SIGUSR1, $func1);
            $loop->removeSignal(SIGUSR2, $func2);
            $loop->clearInterval($timer);
        });
        $loop->addTick(function () {
            posix_kill(posix_getpid(), SIGUSR1);
        });
        $loop->run();
        $this->assertTrue($called);
        $this->assertTrue($calledShouldNot);
    }
	
    public function testSignalMultipleUsagesForTheSameListener()
    {
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $loop = new Loop();
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
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $loop = new Loop();
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
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
        $loop = new Loop();
        $function = function () {};
        $loop->addSignal(SIGUSR1, $function);
        $loop->addTimeout(function () use ($function, $loop) {
            $loop->removeSignal(SIGUSR1, $function);
        }, 1.5);
        $loop->run();
        $this->assertRunFasterThan(1.6);
    }
}
