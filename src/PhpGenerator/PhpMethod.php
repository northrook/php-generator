<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

use Stringable;
use Support\PhpGenerator;
use function Support\str_contains_only;

final class PhpMethod extends PhpFragment
{
    public const string TYPE = 'method';

    protected string $arguments;

    protected string $returnType;

    protected string $code;

    /**
     * @param string            $name
     * @param string|Stringable $code
     * @param string|string[]   $arguments
     * @param string|string[]   $returns
     * @param Visibility        $visibility
     * @param bool              $final
     * @param null|string       $comment
     */
    public function __construct(
        public readonly string $name,
        string|Stringable      $code,
        string|array           $arguments = '',
        string|array           $returns = 'void',
        public Visibility      $visibility = Visibility::PUBLIC,
        public bool            $final = false,
        protected ?string      $comment = null,
    ) {
        $code = PhpGenerator::optimize( $code );

        $code = \explode( "\n", $code );

        foreach ( $code as $line => $string ) {
            if (
                str_contains_only( $string, " \t\0\x0B" )
                && str_contains_only( $code[$line - 1], " \t\0\x0B" )
            ) {
                unset( $code[$line] );

                continue;
            }
            if ( \str_starts_with( $string, 'echo' ) ) {
                break;
            }

            $code[$line] = \str_repeat( '    ', 1 ).$string;
        }

        $this->code = \implode( "\n", $code );
        // each $argument is a string, trim up until $variableName, any before is type declarations
        $this->arguments  = \is_array( $arguments ) ? \implode( ' ', $arguments ) : $arguments;
        $this->returnType = \is_array( $returns ) ? \implode( '|', $returns ) : $returns;
    }

    public function build() : string
    {
        $method = $this->comment ? <<<PHP
            /**
             * {$this->comment}
             */
            PHP."\n" : '';

        $method .= $this->final ? 'final ' : '';
        $method .= "{$this->visibility->value} function {$this->name}";
        $method .= $this->arguments ? "( {$this->arguments} )" : '()';
        $method .= " : {$this->returnType}";

        $output = \explode( "\n", $method );

        foreach ( $output as $line => $string ) {
            $output[$line] = \str_repeat( '    ', 1 ).$string;
        }
        return \implode( "\n", \array_filter( $output ) )."\n\t{\n{$this->code}\n\t}";
    }
}
