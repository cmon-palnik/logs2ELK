<?php

namespace Logs2ELK\EventListener;

use Logs2ELK\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

final readonly class ConsoleErrorListener
{

    public function __construct(
        private LoggerInterface $logger
    )
    {
    }

    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $ex = $event->getError();
/** @todo Recognize Exceptions */

        $msg = '[' . date("Y-m-d H:i:s") . "] EXCEPTION_TRACER " . PHP_EOL;
        $msg .= "[MESSAGE] " . $ex->getMessage() . PHP_EOL;
        if ($ex instanceof Exception) {
            $msg .= "[CONTEXT] " . json_encode($ex->getContext()) . PHP_EOL;
        }
        $msg .= "[TRACE] " . $ex->getTraceAsString() . PHP_EOL;
        echo $msg;
        $this->logger->critical($msg);
    }

}
