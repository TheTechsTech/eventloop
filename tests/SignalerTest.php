<?php

namespace Async\Tests;

use Async\Loop\Signaler;
use PHPUnit\Framework\TestCase;

class SignalerTest extends TestCase
{
	protected function setUp()
    {
        if (!function_exists('posix_kill') || !function_exists('posix_getpid')) {
            $this->markTestSkipped('Signal test skipped because functions "posix_kill" and "posix_getpid" are missing.');
        }
    }
	
    public function testEmittedEventsAndCallHandling()
    {
        $callCount = 0;
        $func = function () use (&$callCount) {
            $callCount++;
        };
        $signals = new Signaler();

        $this->assertSame(0, $callCount);

        $signals->add(SIGUSR1, $func);
        $this->assertSame(0, $callCount);

        $signals->add(SIGUSR1, $func);
        $this->assertSame(0, $callCount);

        $signals->add(SIGUSR1, $func);
        $this->assertSame(0, $callCount);

        $signals->call(SIGUSR1);
        $this->assertSame(1, $callCount);

        $signals->add(SIGUSR2, $func);
        $this->assertSame(1, $callCount);

        $signals->add(SIGUSR2, $func);
        $this->assertSame(1, $callCount);

        $signals->call(SIGUSR2);
        $this->assertSame(2, $callCount);

        $signals->remove(SIGUSR2, $func);
        $this->assertSame(2, $callCount);

        $signals->remove(SIGUSR2, $func);
        $this->assertSame(2, $callCount);

        $signals->call(SIGUSR2);
        $this->assertSame(2, $callCount);

        $signals->remove(SIGUSR1, $func);
        $this->assertSame(2, $callCount);

        $signals->call(SIGUSR1);
        $this->assertSame(2, $callCount);
    }
}
