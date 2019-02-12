<?php declare(strict_types=1);

namespace ReactiveApps\Command\Cron\Command;

use Psr\Log\LoggerInterface;
use ReactiveApps\Command\Command;
use ReactiveApps\Rx\Shutdown;
use WyriHaximus\PSR3\CallableThrowableLogger\CallableThrowableLogger;

final class Cron implements Command
{
    const COMMAND = 'cron';

    /** @var LoggerInterface */
    private $logger;

    /** @var Shutdown */
    private $shutdown;

    /**
     * @param LoggerInterface $logger
     * @param Shutdown $shutdown
     */
    public function __construct(LoggerInterface $logger, Shutdown $shutdown)
    {
        $this->logger = $logger;
        $this->shutdown = $shutdown;
    }

    public function __invoke()
    {
    }
}
