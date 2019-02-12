<?php

use Psr\Log\LoggerInterface;
use ReactiveApps\Command\Cron\Command\Cron;
use ReactiveApps\Command\HttpServer\Command\HttpServer;
use ReactiveApps\Command\HttpServer\ControllerMiddleware;
use ReactiveApps\Command\HttpServer\RequestHandlerMiddleware;
use ReactiveApps\Rx\Shutdown;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;
use WyriHaximus\React\Http\Middleware\RewriteMiddleware;
use WyriHaximus\React\Http\Middleware\WebrootPreloadMiddleware;
use WyriHaximus\React\Http\PSR15MiddlewareGroup\Factory;

return [
    Cron::class => \DI\factory(function (
        LoggerInterface $logger,
        Shutdown $shutdown
    ) {
        $logger = new ContextLogger($logger, ['command' => 'cron'], 'cron');

        return new Cron($logger, $shutdown);
    }),
];
