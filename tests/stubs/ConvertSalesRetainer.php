<?php

namespace Zerp\Retainer\Events;

/**
 * Stands in for the retainer module's event, which is not released yet.
 *
 * The listener type hints the real class, so without this there is nothing to hand it
 * in a test. The shape follows the accounting module's ConvertSalesRetainerListener,
 * which already reads $event->invoice and $event->retainer.
 *
 * Guarded, so the real event wins the moment the retainer module is installed. If its
 * shape turns out to differ, this stub is the thing to correct first: the test using
 * it is what will catch the mismatch.
 */
if (!class_exists(ConvertSalesRetainer::class, false)) {
    class ConvertSalesRetainer
    {
        public $invoice;
        public $retainer;

        public function __construct($invoice = null, $retainer = null)
        {
            $this->invoice = $invoice;
            $this->retainer = $retainer;
        }
    }
}
