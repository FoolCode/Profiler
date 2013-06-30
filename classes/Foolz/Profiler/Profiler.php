<?php

namespace Foolz\Profiler;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class Profiler {
    /**
     * If the data must be logged
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * The instance of the monolog logger
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Handler that holds all data so we can output it in html
     *
     * @var TestHandler
     */
    protected $test_handler;

    /**
     * Start time from
     *
     * @var float
     */
    protected $start_time;

    /**
     * @var float
     */
    protected $start_memory_usage;

    /**
     * @var float
     */
    protected $timer;

    /**
     * Creates a new logger WITHOUT HANDLERS (use pushHandler() to add monolog handlers)
     */
    public function __construct() {
        $this->logger = new Logger('profiler');
        $this->test_handler = new TestHandler();
        $this->logger->pushHandler($this->test_handler);
    }

    /**
     * Returns the logger for fine-grained setting up
     *
     * @return Logger
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * Tells if the profiler is logging data
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Pushes a handler on to the monolog stack.
     * Examples: FirePHPHandler, ChromePHPHandler, RotatingFileHandler...
     *
     * @param HandlerInterface $handler
     *
     * @return $this
     */
    public function pushHandler(HandlerInterface $handler) {
        $this->logger->pushHandler($handler);

        return $this;
    }

    /**
     * Enables the logger and sets the start time and memory usage
     *
     * @param null|float $start_time   A microtime(true) may be provided to give true startup time of the application, else $_SERVER["REQUEST_TIME_FLOAT"] is used
     * @param null|float $memory_usage A memory_get_usage() may be provided to give true startup memory usage of the application, else current is used
     */
    public function enable($start_time = null, $memory_usage = null) {
        $this->enabled = true;
        $this->start_time = $start_time ? : ($_SERVER["REQUEST_TIME_FLOAT"] * 10000);
        $this->start_memory_usage = $memory_usage ? : memory_get_usage();

        $this->log("Profiling enabled");
    }

    /**
     * Logs time and memory usage
     *
     * @param string $string A string to identify the entry in the log
     * @param array $context Arbitrary data to log
     */
    public function log($string, $context = []) {
        if (!$this->enabled) return;

        $this->logger->info($string, [
                'time' => static::formatTime(static::getTime() - $this->start_time),
                'memory' => static::formatSize(memory_get_usage()),
                'memory_bytes' => memory_get_usage(),
                'time_microtime' => static::getTime() - $this->start_time,
            ] + $context);
    }

    /**
     * Logs time, memory usage and memory usage for a single variable
     * Notice that to know the size of the variable we are making a clone via [mem][serialize-unserialize][mem], so use carefully
     *
     * @param string $string   A string to identify the entry in the log, in example the name of the variable
     * @param object $variable The variable or object itself
     * @param array $context   Arbitrary data to log
     */
    public function logMem($string, $variable, $context = []) {
        if (!$this->enabled) return;

        $before = memory_get_usage();
        $var = unserialize(serialize($variable));
        $after = memory_get_usage();
        $this->log($string, [
                'memory_variable' => static::formatSize($after - $before),
                'memory_variable_bytes' => $after - $before
            ] + $context);
    }

    /**
     * Starts a timer. Ideal for logging elapsed time for database queries
     *
     * @param string $string A string to identify the entry in the log
     * @param array $context Arbitrary data to log
     */
    public function logStart($string, $context = []) {
        if (!$this->enabled) return;

        $this->timer = static::getTime();
        $this->log("Start: ".$string, [
            'elapsed' => 'start'
        ] + $context);
    }

    /**
     * Stops the timer
     *
     * @param $string
     * @param array $context
     */
    public function logStop($string, $context = []) {
        if (!$this->enabled) return;

        $this->log("Stop: ".$string, [
                'elapsed' => static::formatTime(static::getTime() - $this->timer),
                'elapsed_microtime' => static::getTime() - $this->timer
            ] + $context);
    }

    public function getHtml() {
        $records = $this->test_handler->getRecords();
        ob_start();
        ?>
            <div style="width:100%; padding:10px 0 20px; background: #f5f5f5;">
                <div style="width: 80%; margin: 0 auto">
                    <h4>Profiler</h4>
                    <p>
                        <strong>Logged</strong>: <?= count($records) ?> entries.
                        <strong>Peak memory usage</strong>: <?= static::formatSize(memory_get_peak_usage()) ?>.
                    </p>
                    <table style="width: 100%; border: 1px solid #cccccc; line-height: 150%">
                        <thead style="text-align: left; border-bottom: 1px solid #cccccc">
                            <?php foreach(['#', 'Message', 'Time', 'Memory', 'Var memory', 'Elapsed'] as $column) : ?>
                                <th style="border-right: 1px solid #cccccc; padding: 0 5px"><?= $column ?></th>
                            <?php endforeach; ?>
                        </thead>
                        <tbody>
                            <?php $i = 0; foreach($records as $key => $record) : ?>
                                <tr style="border-top: 1px solid #cccccc;<?= $key%2 ? 'background:#fbfbfb' : '' ?>">
                                    <td style="border-right: 1px solid #cccccc; padding: 0 5px"><?= ++$i ?></td>
                                    <td style="border-right: 1px solid #cccccc; padding: 0 5px"><?= $record['message'] ?></td>
                                    <?php foreach(['time', 'memory', 'memory_variable', 'elapsed'] as $column) : ?>
                                        <td style="border-right: 1px solid #cccccc; padding: 0 5px">
                                            <?= isset($record['context'][$column]) ? $record['context'][$column] : '<span style="color: #dddddd">N/A</span>' ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Returns the current time in milliseconds
     *
     * @return string
     */
    protected static function getTime() {
        return microtime(true) * 10000;
    }

    /**
     * Pretty-prints the current time
     *
     * @param $time
     * @return string
     */
    protected static function formatTime($time) {
        if ($time > 100000 && $time < 6000000) {
            return sprintf('%02.2f', $time / 1000).'s';
        }

        if ($time > 6000000) {
            return sprintf('%02.2f', $time / 60000).'m';
        }

        return sprintf('%02.2f', $time / 100).'ms';
    }

    /**
     * Returns the size in bytes
     *
     * @param $size
     */
    protected static function formatSize($size) {
        if ($size > 1024 * 1024) {
            return sprintf('%02.2f', $size / (1024 * 1024)).'mb';
        }

        if ($size > 1024) {
            return sprintf('%02.2f', $size / 1024).'kb';
        }
        return $size.'b';
    }
}