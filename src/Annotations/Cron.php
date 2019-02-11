<?php declare(strict_types=1);

namespace ReactiveApps\Command\Cron\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class Cron
{
    /**
     * @var string
     */
    private $method;

    /**
     * @param string[] $methods
     */
    public function __construct(array $methods)
    {
        $this->method = current($methods);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}