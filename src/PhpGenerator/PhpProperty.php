<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

final class PhpProperty extends PhpFragment
{
    public const string TYPE = 'property';

    protected string $type;

    /**
     * @param string          $name
     * @param string|string[] $type
     * @param mixed           $defaultValue
     * @param Visibility      $visibility
     * @param bool            $readonly
     * @param null|string     $comment
     */
    public function __construct(
        public readonly string $name,
        string|array           $type,
        protected mixed        $defaultValue = Argument::UNASSIGNED,
        public Visibility      $visibility = Visibility::PUBLIC,
        public bool            $readonly = false,
        protected ?string      $comment = null,
    ) {
        $this->type = \is_array( $type ) ? \implode( '|', $type ) : $type;
    }

    public function build() : string
    {
        $property = $this->comment ? <<<PHP
            /**
             * {$this->comment}
             */
            PHP."\n" : '';

        $property .= "{$this->visibility->value} ";
        $property .= $this->readonly ? 'readonly ' : '';
        $property .= "{$this->type} \${$this->name}";

        if ( $this->defaultValue !== Argument::UNASSIGNED ) {
            $property .= ' = '.Argument::export( $this->defaultValue );
        }

        return "{$property};";
    }
}
