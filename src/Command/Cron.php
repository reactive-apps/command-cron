<?php declare(strict_types=1);

namespace ReactiveApps\Command\Cron\Command;

use Cron\CronExpression;
use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;
use function React\Promise\resolve;
use ReactiveApps\Command\Cron\Annotations\Cron as CronAnnotation;
use Cake\Collection\Collection;
use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Log\LoggerInterface;
use ReactiveApps\Command\Command;
use ReactiveApps\Rx\Shutdown;
use Recoil\Kernel;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function WyriHaximus\from_get_in_packages_composer;
use function WyriHaximus\iteratorOrArrayToArray;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

final class Cron implements Command
{
    const COMMAND = 'cron';

    /** @var LoopInterface */
    private $loop;

    /** @var LoggerInterface */
    private $logger;

    /** @var ContainerInterface */
    private $container;

    /** @var Kernel */
    private $kernel;

    /** @var Shutdown */
    private $shutdown;

    private $crons = [];

    /**
     * @param LoggerInterface $logger
     * @param Shutdown $shutdown
     */
    public function __construct(LoopInterface $loop, LoggerInterface $logger, ContainerInterface $container, Kernel $kernel, Shutdown $shutdown)
    {
        $this->loop = $loop;
        $this->logger = $logger;
        $this->container = $container;
        $this->kernel = $kernel;
        $this->shutdown = $shutdown;

        try {
            $this->crons = iterator_to_array($this->locateActions());
        } catch (\Throwable $et) {
            echo (string)$et;
        }
    }

    private function locateActions(): iterable
    {
        $annotationReader = new AnnotationReader();
        $betterReflection = new BetterReflection();
        $astLocator = $betterReflection->astLocator();

        foreach (from_get_in_packages_composer('extra.reactive-apps.cron-action') as $action) {
            $reflector = new ClassReflector(new SingleFileSourceLocator($action, $astLocator));
            foreach ($reflector->getAllClasses() as $class) {
                $annotations = (new  Collection($annotationReader->getClassAnnotations(new \ReflectionClass($class->getName()))))
                    ->indexBy(function (object $annotation) {
                        return get_class($annotation);
                    })->toArray();

                if (!isset($annotations[CronAnnotation::class])) {
                    continue;
                }

                yield $class->getName() => [
                    'expression' => CronExpression::factory($annotations[CronAnnotation::class]->getExpression()),
                ];
            }
        }
    }

    public function __invoke()
    {
        yield resolve();

        $this->loop->addPeriodicTimer(60, function () {
            $this->logger->debug('Checking which cron action to run');
            foreach ($this->crons as $class => &$cron) {
                if ($cron['expression']->isDue() === true) {
                    $logger = new ContextLogger($this->logger, ['cron' => $class], $class);
                    if (!isset($cron['instance'])) {
                        $logger->debug('Instantiating');
                        $cron['instance'] = $this->container->get($class);
                    }

                    $this->kernel->execute(function () use ($logger, $cron) {
                        $logger->debug('Running');
                        yield $cron['instance']();
                        $logger->debug('Done');
                    });
                }
            }
            $this->logger->debug('Done');
        });
    }
}
