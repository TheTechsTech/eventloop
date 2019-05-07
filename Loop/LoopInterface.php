<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Loop;

use Async\Loop\ProcessorInterface;

/**
 * A simple event loop implementation.
 *
 * This event loop supports:
 *   * addTick queue callbacks
 *   * addTimeout for delayed functions
 *   * setInterval for repeating functions
 *   * stream events using stream_select
 */
interface LoopInterface
{
	/**
	 * The interval after which this timer will execute, in seconds
	 */    
    const MIN_INTERVAL = 0.000001;

	/**
	 * Retrieves current event Loop object.
	 */
	public static function getInstance();
		
    /**
     * Executes a function after x seconds.
     */
    public function addTimeout(callable $task, float $timeout);

    /**
     * Executes a function every x seconds.
     *
     * The value this function returns can be used to stop the interval with
     * clearInterval.
     */
    public function setInterval(callable $task, float $timeout);

    /**
     * Stops a running interval.
     */
    public function clearInterval(array $intervalId);

    /**
     * Adds a read stream.
     *
     * The callback will be called as soon as there is something to read from
     * the stream.
     *
     * You MUST call removeReadStream after you are done with the stream, to
     * prevent the eventloop from never stopping.
     *
     * @param resource $stream
     */
    public function addReadStream($stream, $task);

    /**
     * Adds a write stream.
     *
     * The callback will be called as soon as the system reports it's ready to
     * receive writes on the stream.
     *
     * You MUST call removeWriteStream after you are done with the stream, to
     * prevent the eventloop from never stopping.
     *
     * @param resource $stream
     */
    public function addWriteStream($stream, $task);
	
    /**
     * Stop watching a stream for reads.
     *
     * @param resource $stream
     */
    public function removeReadStream($stream);	

    /**
     * Stop watching a stream for writes.
     *
     * @param resource $stream
     */
    public function removeWriteStream($stream);

    /**
     * Runs the loop.
     *
     * This function will run continuously, until there's no more events to handle.
	 *
	 * @param callable()|null $initialize
     */
    public function run();

    /**
     * Executes all pending events.
     *
     * If $block is turned true, this function will block until any event is
     * triggered.
     *
     * If there are now timeouts, addTick callbacks or events in the loop at
     * all, this function will exit immediately.
     *
     * This function will return true if there are _any_ events left in the
     * loop after the tick.
	 *
	 * @param bool $block
     */
    public function tick(bool $block = false);

    /**
     * Stops a running eventloop.
     */
    public function stop();

    /**
     * Add an function to run immediately at the next iteration of the loop.
     */
    public function addTick(callable $task);

	 /**
     * Register a listener to be notified when a signal has been caught by this process.
     *
     * This is useful to catch user interrupt signals or shutdown signals from
     * tools like `supervisor` or `systemd`.
     *
     * The listener callback function MUST be able to accept a single parameter,
     * the signal added by this method or you MAY use a function which
     * has no parameters at all.
     *
     * The listener callback function MUST NOT throw an `Exception`.
     * The return value of the listener callback function will be ignored and has
     * no effect, so for performance reasons you're recommended to not return
     * any excessive data structures.
     *
     * ```php
     * $loop->addSignal(SIGINT, function (int $signal) {
     *     echo 'Caught user interrupt signal' . PHP_EOL;
     * });
     * ```
     *
     * See also [example #4](examples).
     *
     * Signaling is only available on Unix-like platform, Windows isn't
     * supported due to operating system limitations.
     * This method may throw a `BadMethodCallException` if signals aren't
     * supported on this platform, for example when required extensions are
     * missing.
     *
     * **Note: A listener can only be added once to the same signal, any
     * attempts to add it more then once will be ignored.**
     *
     * @param int $signal
     * @param callable $listener
     *
     * @throws \BadMethodCallException when signals aren't supported on this
     *     platform, for example when required extensions are missing.
     *
     * @return void
     */
    public function addSignal($signal, callable $listener);
	
    /**
     * Removes a previously added signal listener.
     *
     * ```php
     * $loop->removeSignal(SIGINT, $listener);
     * ```
     *
     * Any attempts to remove listeners that aren't registered will be ignored.
     *
     * @param int $signal
     * @param callable $listener
     *
     * @return void
     */
    public function removeSignal($signal, callable $listener);
}
