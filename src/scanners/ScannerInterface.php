<?php

namespace lajax\projectscanner\scanners;

/**
 * Scanner interface
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
interface ScannerInterface
{
    /**
     * Executes the scanning statement.
     */
    public function execute();
}
