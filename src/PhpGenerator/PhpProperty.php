<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

final class PhpProperty extends PhpFragment
{
    public const string TYPE = 'property';

    protected string $arguments;

    protected string $returnType;

    /**
     * @param string          $name
     * @param string          $code
     * @param string|string[] $arguments
     * @param string|string[] $returns
     * @param Visibility      $visibility
     * @param null|string     $comment
     */
    public function __construct(
        public readonly string $name,
        protected string       $code,
        string|array           $arguments = '',
        string|array           $returns = 'void',
        public Visibility      $visibility = Visibility::PUBLIC,
        protected ?string      $comment = null,
    ) {
        // each $argument is a string, trim up until $variableName, any before is type declarations
        $this->arguments  = \is_array( $arguments ) ? \implode( ' ', $arguments ) : $arguments;
        $this->returnType = \is_array( $returns ) ? \implode( '|', $returns ) : $returns;
    }

    public function build() : string
    {
        return __METHOD__;
    }
}
