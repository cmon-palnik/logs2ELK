<?php

namespace Logs2ELK\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $ex = $event->getThrowable();

        $msg = date("Y-m-d H:i:s") . " GLOBAL EXCEPTION " . PHP_EOL;
        $msg .= $ex->getMessage() . PHP_EOL;
        $msg .= $ex->getTraceAsString() . PHP_EOL;
        echo $msg;
    }
}
