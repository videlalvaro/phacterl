# Phacterl #

Implementation of The Actor Model in PHP.

## Installation ##

Add `"videlalvaro/phacterl": "dev-master"` to your `composer.json` `require` section.

## Usage ##

Your _Actors_ need to extend the abstract class Actor for example, and
implement two methods: `Actor::init` and `Actor::receive`. For
example:

```php
class Counter extends Actor {
    public function init($args) {
        return array(
            'count' => 0
        );
    }

    public function receive() {
        return array('incr', 'get_count');
    }
}


The method init should return an array that would be the _State_ of the process. The runtime system will take care of managing the process state.

The method `receive` should return an array with strings specifying to which _message tags_ this process responds to.

In our case, the process `Counter` should also provide a method called `handle_incr` and another one called `handle_get_count`.

### Implementing the Handlers ###

A handler is a function that takes two parameters, a `Message` and the process `State` and returns a new state, like this:

```
class Counter extends Actor {
// snip

    public function handle_incr($msg, $state) {
        $state['counter'] += $msg['amount'];
        return $state;
    }

// snip
}
```

### Sending messages ###

To send a message, your actor can call the function `Actor::send`,
which expects a _process id_ and a `Message`. When creating a
`Message` instance, you need to provide a `tag`, like `'count'` in
this case, and the message data. The _tag_ is used to dispatch to a
message handler called `handle_<tag>`.

```php
class Counter extends Actor {
// snip

    public function handle_get_count($msg, $state) {
        $pid = $msg['sender'];
        $this->send(
                $pid,
                new Message(
                    'count',
                    array('sender' => $this->self(), 'count' => $state['counter'])
                )
            );
        return $state;
    }

// snip
}
```

Then, once you to run your actors, first get an instance of the `Scheduler`, and _spawn_ your actor, by passing in the class name and the initial parameters for the Actor's _init_ function.

```php
$scheduler = new Scheduler();
$pid = $scheduler->spawn('Counter', array());
$scheduler->run();
```

## Examples ##

On the demo folder you can find many examples which are
implementations of the algorithms presented in the book
[Distributed Algorithms for Message-Passing Systems](http://www.amazon.com/Distributed-Algorithms-Message-Passing-Systems-Michel/dp/3642381227/).

## Why? ##

Because implementing these algorithms in Erlang would be too easy.

## LICENSE ##

The MIT License (MIT)

Copyright (c) 2014 - Alvaro Videla

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
