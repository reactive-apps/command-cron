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
    private $expression;

    /**
     * @param string[] $expressions
     */
    public function __construct(array $expressions)
    {
        $this->expression = current($expressions);
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }
}
