<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use ReactiveApps\Command\Cron\Command\Cron;
use ReactiveApps\LifeCycleEvents\Promise\Shutdown;
use Recoil\Kernel;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

return [
    Cron::class => \DI\factory(function (
        LoggerInterface $logger,
        LoopInterface $loop,
        ContainerInterface $container,
        Kernel $kernel,
        Shutdown $shutdown
    ) {
        $logger = new ContextLogger($logger, ['command' => 'cron'], 'cron');

        return new Cron($loop, $logger, $container, $kernel, $shutdown);
    }),
];
