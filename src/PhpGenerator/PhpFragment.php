<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

abstract class PhpFragment
{
    public const string TYPE = 'fragment';

    public bool $print = true;

    public readonly string $name;

    abstract protected function build() : string;

    final public function resolve() : ?string
    {
        return $this->print ? $this->build() : null;
    }

    final protected function dump( mixed $value, ?bool $multiline = null ) : string
    {
        if ( \is_array( $value ) ) {
            $indexed = $value && \array_keys( $value ) === \range( 0, \count( $value ) - 1 );
            $s       = '';

            foreach ( $value as $k => $v ) {
                $s .= $multiline
                        ? ( $s === '' ? "\n" : '' )."\t".( $indexed ? '' : self::dump( $k ).' => ' ).self::dump(
                            $v,
                        ).",\n"
                        : ( $s === '' ? '' : ', ' ).( $indexed ? '' : self::dump( $k ).' => ' ).self::dump( $v );
            }

            return '['.$s.']';
        }
        if ( $value === null ) {
            return 'null';
        }

        return \var_export( $value, true );
    }
}
