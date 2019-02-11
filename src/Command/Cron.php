<?php declare(strict_types=1);

namespace ReactiveApps\Command\Cron\Command;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server as ReactHttpServer;
use React\Socket\Server as SocketServer;
use ReactiveApps\Command\Command;
use ReactiveApps\Rx\Shutdown;
use WyriHaximus\PSR3\CallableThrowableLogger\CallableThrowableLogger;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

final class Cron implements Command
{
    const COMMAND = 'cron';

    public function __invoke()
    {
    }
}
