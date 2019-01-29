Event Loop
=====

This library's core uses the [reactor pattern](https://en.wikipedia.org/wiki/Reactor_pattern) to handle normally [blocking I/O](https://nodejs.org/en/docs/guides/blocking-vs-non-blocking/#blocking) function/event __calls__.

Concepts
---

[The Reactor and Singleton Pattern](https://youtu.be/pmtrUcPs4GQ) __video__

[Patterns and Frameworks for Synch Event Handling, Connections, and Service Initialization](https://youtu.be/m-J9FCFOMUE) __video__

The [_reactor pattern_](https://youtu.be/izdvImum8ow) is an event handling pattern for handling service requests delivered concurrently to a service handler by one or more inputs. @see [Reactor](https://github.com/tpn/pdfs/blob/master/Reactor%20-%20An%20Object%20Behavioral%20Pattern%20for%20Demultiplexing%20and%20Dispatching%20Handles%20for%20Synchronous%20Events.pdf) - An Object Behavioral Pattern for Demultiplexing and Dispatching Handles for Synchronous Events.

PHP wasn't built from the ground up with an [Event Loop](https://en.wikipedia.org/wiki/Event_loop) concept in mind, like other Languages, _Python_, _JavaScript_ for one.

Like _Python_ and _JavaScript_, _PHP_ is single threaded. It can handle asynchronous event base programming quite well, however there is no standard library way to implement.

[Event Loop From the Inside Out](https://youtu.be/P9csgxBgaZ8) __video__

[Help I'm stuck in an event loop](https://youtu.be/6MXRNXXgP_0) __video__

[What Is Async, How Does It Work, and When Should I Use It?](https://youtu.be/kdzL3r-yJZY) __video__

In order to have any async behavior programming, the based libraries needs to be interoperable, and they need to use the same event loop.

This component provides a common `LoopInterface` that any library can target. This allows them to be used in the same loop, with one single [`run()`](#run) call that is controlled by the user.

**Table of Contents**
---

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Loop](#Loop)
    * [getInstance()](#getInstance)
  * [Loop implementations](#loop-implementations)
    * [Stream_Select()](Stream_Select)
  * [LoopInterface](#loopinterface)
    * [getInstance()](#getInstance)
    * [addTimeout()](#addTimeout)
    * [setInterval()](#setInterval)
    * [clearInterval()](#clearInterval)
    * [addReadStream()](#addReadStream)
    * [addWriteStream()](#addWriteStream)
    * [removeReadStream()](#removeReadStream)
    * [removeWriteStream()](#removeWriteStream)
    * [run()](#run)
    * [tick()](#tick)
    * [stop()](#stop)
    * [addTick()](#addTick)
    * [addSignal()](#addsignal)
    * [removeSignal()](#removesignal)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
