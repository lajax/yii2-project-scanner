<?php

namespace lajax\projectscanner;

use Yii;
use STDOUT;
use STDERR;
use PHP_EOL;
use yii\helpers\Console;

/**
 * Prints a string to console.
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
trait ConsoleOutputTrait
{

    /**
     * Returns a value indicating whether ANSI color is enabled.
     *
     * @param resource $stream the stream to check.
     * @return boolean Whether to enable ANSI style in output.
     */
    public function isColorEnabled($stream = STDOUT)
    {
        return Console::streamSupportsAnsiColors($stream);
    }

    /**
     * Prints a string to STDOUT
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\yii\helpers\Console]].
     *
     * Example:
     *
     * ~~~
     * $this->stdout('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ~~~
     *
     * @param string $string the string to print
     * @return int|boolean Number of bytes printed or false on error
     */
    public function stdout($string)
    {
        if (!Yii::$app->request->isConsoleRequest) {
            return;
        }

        if ($this->isColorEnabled()) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }
        return Console::stdout($string . PHP_EOL);
    }

    /**
     * Prints a string to STDERR
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\yii\helpers\Console]].
     *
     * Example:
     *
     * ~~~
     * $this->stderr('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ~~~
     *
     * @param string $string the string to print
     * @return int|boolean Number of bytes printed or false on error
     */
    public function stderr($string)
    {
        if (!Yii::$app->request->isConsoleRequest) {
            return;
        }

        if ($this->isColorEnabled(STDERR)) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }
        return Console::stderr($string . PHP_EOL);
    }

}
