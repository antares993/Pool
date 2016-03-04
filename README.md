# Pool

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/77d14077-a55c-4282-8f27-c8475d1bf7da/mini.png)](https://insight.sensiolabs.com/projects/77d14077-a55c-4282-8f27-c8475d1bf7da)
[![Build Status](https://travis-ci.org/antares993/Pool.svg?branch=master)](https://travis-ci.org/antares993/Pool)
[![Latest Stable Version](https://poser.pugx.org/antares/pool/v/stable)](https://packagist.org/packages/antares/pool)
[![License](https://poser.pugx.org/antares/pool/license)](https://packagist.org/packages/antares/pool)


This library provides you simple pools. This is especially useful when working with [React](http://reactphp.org/): this way you may share resources such as database connections and boost your application's performance.

**Suggestions and contributions are welcome!**

## Install

You can add this library as a dependency using composer this way:

```
composer require antares/pool
```

## Compatibility

This library is compatible with PHP 5.4+, PHP 7 and HHVM.

## Documentation

### Resources accepted by the pools

First of all, please note that the pools provided only store objects. Values such as integers, string or arrays cannot be stored. If needed, you can wrap these values into a `stdClass`.

### `PoolInterface`

The pools provided implement the `PoolInterface` interface, which provides the following methods:

```php
interface PoolInterface
{
    // Associate a resource to an id
    public function set($id, $generator, $eventsCallbacks = null);

    // Get an instance of the resource
    public function get($id);

    // Free the instance of the resource
    public function dispose($instance);

    // Clear the pool
    public function clear();

    // Set a set of callbacks for the events a resource can trigger
    public function addEventsCallbacks($id, $callbacks);

    // Set a callback to call when an event is triggered for given resource
    public function addEventCallback($id, $event, $callback);
}
```

### `Pool`

The most basic pool provided is the `Pool` class. Let's see an example of how it can be used.

```php
use Pool\Pool;

class Foo {}

$pool = new Pool(); // Instantiate the pool
$pool->set('foo', function() { return new Foo(); }); // Assign to the id 'foo' a generator returning an instance of Foo

// If an instance is already available, the pool will return it
// Else, a new instance will be created
$foo = $pool->get('foo');

// We don't need $foo anymore, so the instance can be released
// It will be available from the pool again
$pool->dispose($foo);

```

As shown in this example, it is important to release the resource when it is not needed anymore. If you don't, a new instance will be created each time the resource will be asked, and will stay in the memory.

### `StaticPool`

Given the structure of your application, the code where you define your pool may be uselessly executed several times. In this case, the `StaticPool` can be useful. Internally, it contains a static instance of `Pool` which is shared between every instance of `StaticPool`. Take a look at the following piece of code:

```php
use Pool\StaticPool;

class Foo {}

$pool = new StaticPool();

if (!$pool->isAlreadyDefined()) {
    $pool->set('foo', function() { return new Foo(); });
}

$foo = $pool->get('foo');
$pool->dispose($foo);
```

Here, the first time the pool is instanciated, its resources will be defined. The following times, the definition step will be skipped. Each instance of `StaticPool` will use the same internal pool and its use is eased.

### Pool events

You can attach callbacks that will be called when some events are triggered by the pool.

4 events may happen:

- `PoolInterface::EVENT_GET` when the instance of a resource is pulled from the pool (when calling `$pool->get($id)`)
- `PoolInterface::EVENT_DISPOSE` when the instance is released (when calling `$pool->dispose($instance)`)
- `PoolInterface::EVENT_CREATE` when the instance is created (when calling `$pool->get($id)` if the instance has not been already created)
- `PoolInterface::EVENT_DESTRUCT` when the instance is destructed (when calling `$pool->clear()`)

There are 3 ways to define callbacks:

```php
use Pool\PoolInterface;

$pool = new Pool\Pool();

$callbacks = [
    PoolInterface::EVENT_GET => [function($instance) {}],
    PoolInterface::EVENT_DISPOSE => [function($instance) {}]
];

// 1. Define it with the generator
$pool->set('foo', function() { return new Foo(); }, $callbacks);

// 2. Define each event
$pool->addEventCallback('foo', PoolInterface::EVENT_GET, $callbacks[PoolInterface::EVENT_GET][0]);
$pool->addEventCallback('foo', PoolInterface::EVENT_DISPOSE, $callbacks[PoolInterface::EVENT_DISPOSE][0]);

// 3. Define all callbacks in one method call
$pool->addEventsCallbacks('foo', $callback);
```

Note that the callbacks are combinable.

```php
$pool->addEventCallback('foo', PoolInterface::EVENT_GET, function($instance) { echo 'a'; });
$pool->addEventCallback('foo', PoolInterface::EVENT_GET, function($instance) { echo 'b'; });

$pool->get('foo'); // will echo 'a' and 'b'
```

Below is an example of how you can use callbacks.

```php
class Foo {
    public function bar() { echo 'blablabla'; }
}

$counter = 0;
$pool = new Pool\Pool();
$pool->set('foo', function() { return new Foo(); }, [
    PoolInterface::EVENT_GET => [function($instance) { $instance->bar(); }],
    PoolInterface::EVENT_DISPOSE => [function($instance) use (&$counter) { $counter++; }]
]);

$foo = $pool->get('foo'); // $foo->bar() is called

$pool->dispose($foo); // the counter is incremented
```
