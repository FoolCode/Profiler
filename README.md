Foolz\Profiler
========

This package provides a simple to use profiler, with the power of monolog.

## Requirements

* PHP 5.4 or higher
* Monolog (automatically installed with composer)

## Installation

Install as any composer package.

## Setup

You should load the profiler early in your code.

```
<?php
$profiler = new Profiler();
$profiler->enable();
```

Until enable isn't run, no request will be logged. You can setup monolog handlers to have custom output options:

```
<?php
$profiler = new Profiler();
$profiler->pushHandler(new ChromePHPHandler());
$profiler->enable();
```

## HTML output

Remember to check whether you have a `text/html` request before inserting the HTML profiler panel.

You can print the current log at any time with `$profiler->getHtml()`.

If you use a framework you might have a `$response` variable that handles the data sent to the client.
To put the profiler at the bottom of the page, you may try something similar to the following.

```
<?php
$content = explode('</body>', $response->getContent());
if (count($content) == 2) {
    $response->setContent($content[0].$this->profiler->getHtml().$content[1]);
}

$response->send();
```

## Methods

* `pushHandler()`
    As Monolog's pushHandler() function, allows adding log handlers to the logger

* `getLogger()`
    Returns the Monolog logger so it can be customized

* `log($string, $context)`
    Logs elapsed time since the beginning of the script and total memory usage
    The $string variable allows setting a string to identify the entry in the log
    The $context variable allows adding arbitrary data to the entry

* `logMem($string, $variable, $context)`
    Logs memory usage of `$variable`. While checking, a clone of $variable will be created (it can't be helped), so use with caution.
    For the rest, it works like `log()`

* `logStart($string, $context)`
    Like `log()` but it starts a timer

* `logStop($string, $context)`
    Like `log()`, but if called after `logStart()`, `$context` will contain elapsed time

* `getHtml()`
    Returns an HTML representation of the logged entries

