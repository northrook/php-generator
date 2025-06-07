<?php

declare(strict_types=1);

namespace Support;

use Core\Interface\Printable;

use JetBrains\PhpStorm\{Language};
use Stringable;

class PhpGenerator implements Printable
{
    private ?string $raw = null;

    protected string $php;

    /** @var array<string, bool> */
    protected array $uses = [];

    protected string $generator;

    protected ?string $comment = null;

    /**
     * @param string          $name
     * @param null|string     $namespace
     * @param string|string[] $uses
     * @param null|string     $generator
     * @param bool            $strict
     */
    public function __construct(
        public readonly string  $name,
        public readonly ?string $namespace = null,
        string|array            $uses = [],
        ?string                 $generator = null,
        public bool             $strict = false,
    ) {
        $this->generator = $generator ?? $this::class;

        $this->uses( ...( \is_string( $uses ) ? [$uses] : $uses ) );
    }

    /**
     * @param string ...$php
     *
     * @return $this
     */
    final public function raw(
        #[Language( 'PHP' )]
        string ...$php,
    ) : self {
        $this->raw = \implode( NEWLINE, $php ) ?: null;

        return $this;
    }

    public function uses( string ...$fqn ) : self
    {
        foreach ( $fqn as $use ) {
            $this->uses[$use] = true;
        }
        return $this;
    }

    public function __toString() : string
    {
        $this->generate();

        $dateTime = Time::now();

        $timestamp       = $dateTime->unixTimestamp;
        $storageDataHash = key_hash( 'xxh64', $this->php );
        $rawPhpString    = \str_starts_with( $this->php, '<?php' )
                ? \substr( $this->php, 5 )
                : $this->php;

        // dump( $rawPhpString );
        $php = <<<PHP
            <?php
                   
            /*------------------------------------------------------%{$timestamp}%-
                   
               Name      : {$this->name}
               Generated : {$dateTime->format( 'Y-m-d H:i:s e' )}
               Generator : {$this->generator}
                   
               Do not edit it manually.
                   
            -#{$storageDataHash}#------------------------------------------------*/
            PHP.$rawPhpString;

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
        if ( isset( $this->php ) && ! $regenerate ) {
            return $this->php;
        }

        $this->php = $this->generateHead();

        return $this->php;
    }

    protected function generateHead() : string
    {
        $php = ['<?php'];

        if ( $this->strict ) {
            $php['strict'] = 'declare(strict_types=1);';
        }

        if ( $this->namespace ) {
            $php['namespace'] = 'namespace '.$this->namespace.';';
        }

        if ( $this->uses ) {
            foreach ( $this->getUses() as $use ) {
                $php['use'][] = "use {$use};";
            }

            if ( \array_key_exists( 'use', $php ) ) {
                $php['use'] = \implode( "\n", $php['use'] );
            }
        }

        if ( $this->raw ) {
            $php['raw'] = $this->raw;
        }

        return \trim( \implode( "\n\n", $php ) );
    }

    /**
     * @return string[]
     */
    final protected function getUses() : array
    {
        return \array_keys( \array_filter( $this->uses ) );
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
        dump( $this );
        return $this->__toString();
    }

    final public function print() : void
    {
        echo $this->__toString();
    }
}
