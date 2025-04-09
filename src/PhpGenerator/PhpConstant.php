<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

final class PhpConstant extends PhpFragment
{
    public const string TYPE = 'const';

    /**
     * @param string      $name
     * @param mixed       $value
     * @param Visibility  $visibility
     * @param null|string $comment
     * @param null|string $type
     */
    public function __construct(
        public readonly string $name,
        public mixed           $value,
        public Visibility      $visibility = Visibility::PUBLIC,
        protected ?string      $comment = null,
        protected ?string      $type = null,
    ) {}

    public function build() : string
    {
        $this->type ??= \gettype( $this->value );

        return "{$this->visibility->label()} const {$this->type} {$this->name} = {$this->dump( $this->value )};";
    }
}
