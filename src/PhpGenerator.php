<?php

declare(strict_types=1);

namespace Support;

use Core\Interface\Printable;

use Stringable;

class PhpGenerator implements Printable
{
    protected string $php = '';

    protected string $generator;

    protected ?string $comment = null;

    /**
     * @param string      $name
     * @param null|string $generator
     * @param bool        $strict
     */
    public function __construct(
        public readonly string $name,
        ?string                $generator = null,
        public bool            $strict = false,
    ) {
        $this->generator = $generator ?? $this::class;
    }

    public function __toString() : string
    {
        $dateTime = Time::now();

        $timestamp       = $dateTime->unixTimestamp;
        $storageDataHash = key_hash( 'xxh64', $this->php );

        $php = <<<PHP
            <?php
                   
            /*------------------------------------------------------%{$timestamp}%-
                   
               Name      : {$this->name}
               Generated : {$dateTime->format( 'Y-m-d H:i:s e' )}
               Generator : {$this->generator}
                   
               Do not edit it manually.
                   
            -#{$storageDataHash}#------------------------------------------------*/
            PHP.\substr( $this->php, 5 );

        $output = \explode( NEWLINE, \str_replace( ["\r\n", "\r", "\n"], NEWLINE, $php ) );

        foreach ( $output as $line => $string ) {
            if ( \strspn( $string, " \t\0\x0B" ) === \strlen( $string ) ) {
                $output[$line] = '';

                continue;
            }

            if ( $string[0] === "\t" ) {
                $tabs          = \strspn( $string, " \t\0\x0B" );
                $output[$line] = \str_repeat( '    ', $tabs ).\substr( $string, $tabs );
            }
        }

        return \trim( \implode( NEWLINE, $output ) ).NEWLINE;
    }

    public function generate( bool $regenerate = false ) : string
    {
        if ( $this->php && ! $regenerate ) {
            return $this->php;
        }

        return __METHOD__;
    }

    public static function dump( mixed $value, bool $multiline = false ) : string
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

    /**
     * @param string|string[]|Stringable $string
     *
     * @return string
     */
    public static function optimize( Stringable|string|array $string ) : string
    {
        $string = \trim( (string) ( \is_array( $string ) ? \implode( "\n", $string ) : $string ) );

        if ( ! \str_starts_with( $string, '<?php' ) ) {
            $string = '<?php '.$string;
        }

        $outout = '';
        $tokens = \token_get_all( $string );
        $start  = null;
        $string = '';

        for ( $i = 0; $i < \count( $tokens ); $i++ ) {
            $token = $tokens[$i];
            if ( $token[0] === T_ECHO ) {
                if ( ! $start ) {
                    $string = '';
                    $start  = \strlen( $outout );
                }
            }
            elseif ( $start && $token[0] === T_CONSTANT_ENCAPSED_STRING && $token[1][0] === "'" ) {
                $string .= \stripslashes( \substr( $token[1], 1, -1 ) );
            }
            elseif ( $start && $token === ';' ) {
                if ( $string !== '' ) {
                    $outout = \substr_replace(
                        $outout,
                        'echo '.( $string === "\n" ? '"\n"' : \var_export( $string, true ) ),
                        $start,
                        \strlen( $outout ) - $start,
                    );
                }
            }
            elseif ( $token[0] !== T_WHITESPACE ) {
                $start = null;
            }

            $outout .= \is_array( $token ) ? $token[1] : $token;
        }

        return \trim( \substr( $outout, 5 ) );
    }

    public function comment( ?string $comment ) : self
    {
        $this->comment = $comment;
        return $this;
    }

    // :: :: :: :: :: ::

    final public function toString() : string
    {
        return $this->__toString();
    }

    final public function print() : void
    {
        echo $this->__toString();
    }
}
