<?php declare(strict_types=1);

namespace ReactiveApps\Command\Cron\Command;

use Cake\Collection\Collection;
use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use ReactiveApps\Command\Command;
use ReactiveApps\Command\Cron\Annotations\Cron as CronAnnotation;
use ReactiveApps\Rx\Shutdown;
use Recoil\Kernel;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;
use WyriHaximus\React\Action;
use WyriHaximus\React\ActionInterface;
use WyriHaximus\React\Cron as Scheduler;
use function React\Promise\resolve;
use function WyriHaximus\from_get_in_packages_composer;
use function WyriHaximus\iteratorOrArrayToArray;

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

    /** @var ActionInterface[] */
    private $crons;

    private $cronInstances = [];

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
            $this->crons = iteratorOrArrayToArray($this->locateActions());
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

                $className = $class->getName();
                yield new Action(
                    $className,
                    $annotations[CronAnnotation::class]->getExpression(),
                    function () use ($className) {
                        return new Promise(function ($resolve, $reject) use ($className) {
                            $logger = new ContextLogger($this->logger, ['cron' => $className], $className);
                            if (!isset($this->cronInstances[$className])) {
                                $logger->debug('Instantiating');
                                $this->cronInstances[$className] = $this->container->get($className);
                            }

                            $this->kernel->execute(function () use ($logger, $className, $resolve, $reject) {
                                try {
                                    $logger->debug('Running');
                                    $result = yield $this->cronInstances[$className]();
                                    $logger->debug('Done');
                                    $resolve($result);
                                } catch (\Throwable $throwable) {
                                    $reject($throwable);
                                }
                            });
                        });
                    }
                );
            }
        }
    }

    public function __invoke()
    {
        yield resolve();

        Scheduler::create($this->loop, ...$this->crons);
    }
}
