<?php

namespace Async\Tests;

use Async\Loop\Loop;
use PHPUnit\Framework\TestCase;

class LoopTest extends TestCase 
{
    protected $loop;

	protected function setUp(): void
    {
		Loop::clearInstance();
        $this->loop = Loop::getInstance();
    }

    function testAddTick() 
	{
        $loop = $this->loop;
        $check  = 0;
        $loop->addTick(function() use (&$check) {
            $check++;
        });
        $loop->run();
        $this->assertEquals(1, $check);
    }

    function testTimeout() 
	{
        $loop = $this->loop;
        $check  = 0;
        $loop->addTimeout(function() use (&$check) {
            $check++;
        }, 0.02);
        $loop->run();
        $this->assertEquals(1, $check);
    }

    function testTimeoutOrder() 
	{
        $loop = $this->loop;
        $check  = [];
        $loop->addTimeout(function() use (&$check) {
            $check[] = 'a';
        }, 0.2);
        $loop->addTimeout(function() use (&$check) {
            $check[] = 'b';
        }, 0.1);
        $loop->addTimeout(function() use (&$check) {
            $check[] = 'c';
        }, 0.3);
        $loop->run();
        $this->assertEquals(['b', 'a', 'c'], $check);
    }

    function testSetInterval() 
	{
        $loop = $this->loop;
        $check = 0;
        $intervalId = null;
        $intervalId = $loop->setInterval(function() use (&$check, &$intervalId, $loop) {
            $check++;
            if ($check > 5) {
                $loop->clearInterval($intervalId);
            }
        }, 0.02);
        $loop->run();
        $this->assertEquals(6, $check);
    }

    function testStop() 
	{
        $check = 0;
        $loop = $this->loop;
        $loop->addTimeout(function() use (&$check) {
            $check++;
        }, 200);
        $loop->addTick(function() use ($loop) {
            $loop->stop();
        });
        $loop->run();
        $this->assertEquals(0, $check);
    }

    function testTick() 
	{
        $check = 0;
        $loop = $this->loop;
        $loop->addTimeout(function() use (&$check) {
            $check++;
        }, 1);
        $loop->addTick(function() use ($loop, &$check) {
            $check++;
        });
        $loop->tick();
        $this->assertEquals(1, $check);
    }

    /**
     * Here we add a new addTick function as we're in the middle of a current
     * add.
     */
    function testAddStacking() 
	{
        $loop = $this->loop;
        $check  = 0;
        $loop->addTick(function() use (&$check, $loop) {
            $loop->addTick(function() use (&$check) {
                $check++;
            });
            $check++;
        });
        $loop->run();

        $this->assertEquals(2, $check);
    }	
}
