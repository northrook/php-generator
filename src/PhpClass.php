<?php

declare(strict_types=1);

namespace Support;

use Override;
use Stringable;
use Support\PhpGenerator\{
    PhpConstant,
    PhpFragment,
    PhpMethod,
    PhpProperty,
    Visibility,
};

class PhpClass extends PhpGenerator
{
    public readonly string $className;

    public readonly ?string $namespace;

    protected bool $abstract = false;

    protected bool $final = false;

    /** @var array<string, bool> */
    protected array $uses = [];

    /** @var array<string, bool> */
    protected array $extends = [];

    /** @var array<string, bool> */
    protected array $implements = [];

    /** @var array<string, bool> */
    protected array $traits = [];

    /** @var PhpConstant[] */
    protected array $constants = [];

    /** @var PhpProperty[] */
    protected array $properties = [];

    /** @var PhpMethod[] */
    protected array $methods = [];

    protected ?string $comment = null;

    /**
     * @param string          $className
     * @param string|string[] $uses
     * @param null|string     $namespace
     * @param null|string     $generator
     * @param bool            $strict
     */
    public function __construct(
        string       $className,
        string|array $uses = [],
        ?string      $namespace = null,
        ?string      $generator = null,
        public bool  $strict = false,
    ) {
        $this->className = $className;

        if ( $position = \strrpos( $className, '\\' ) ) {
            $namespace ??= \trim( \substr( $className, 0, $position ), " \n\r\t\v\0\\" );
            $className = \trim( \substr( $className, $position ), " \n\r\t\v\0\\" );
        }

        parent::__construct( $className, $generator, $strict );
        $this->namespace = $namespace;

        $this->uses( ...( \is_string( $uses ) ? [$uses] : $uses ) );
    }

    #[Override]
    public function generate( bool $regenerate = false ) : string
    {
        if ( $this->php && ! $regenerate ) {
            return $this->php;
        }

        return $this->php = <<<PHP
            {$this->generateHead()}
                   
            {$this->generateClass()}
            {
                {$this->generateBody()}
            }
            PHP;
    }

    /**
     * @return string[]
     */
    final protected function getUses() : array
    {
        return \array_keys( \array_filter( $this->uses ) );
    }

    private function generateHead() : string
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

        return \trim( \implode( "\n\n", $php ) );
    }

    private function generateClass() : string
    {
        $comment = $this->comment ? <<<PHP
            /**
             * {$this->comment}
             */
            PHP."\n" : null;

        $class = match ( true ) {
            $this->final    => 'final',
            $this->abstract => 'abstract',
            default         => '',
        };

        $class .= ' class ';

        $class .= $this->className;

        if ( $this->extends ) {
            $class .= ' extends ';

            foreach ( $this->extends as $extend => $enabled ) {
                $class .= "{$extend}, ";
            }

            $class = \rtrim( $class, ' ,' );
        }

        if ( $this->implements ) {
            $class .= ' implements ';

            foreach ( $this->implements as $interface => $enabled ) {
                $class .= "{$interface}, ";
            }

            $class = \rtrim( $class, ' ,' );
        }

        return $comment.NEWLINE.\trim( $class );
    }

    private function generateBody() : string
    {
        $php = [];

        if ( $this->traits ) {
            foreach ( $this->traits as $trait => $enabled ) {
                $php['traits'][] = "\tuse {$trait};";
            }

            $php['traits'] = \implode( "\n", $php['traits'] );
        }

        foreach ( $this->getFragments() as $fragment ) {
            $tab = $fragment::TYPE === 'method' ? '' : "\t";

            $php[$fragment::TYPE.".{$fragment->name}"] = $tab.$fragment->resolve();
        }

        return \trim( \implode( "\n\n", $php ) );
    }

    public function uses( string ...$fqn ) : self
    {
        foreach ( $fqn as $use ) {
            $this->uses[$use] = true;
        }
        return $this;
    }

    public function final( bool $set = true ) : self
    {
        $this->final = $set;
        return $this;
    }

    public function abstract( bool $set = true ) : self
    {
        $this->abstract = $set;
        return $this;
    }

    public function extends( string $className ) : self
    {
        $this->extends[$className] = true;
        return $this;
    }

    public function implements( string $className ) : self
    {
        $this->implements[$className] = true;
        return $this;
    }

    public function traits( string $className ) : self
    {
        $this->traits[$className] = true;
        return $this;
    }

    public function comment( ?string $comment ) : self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return PhpFragment[]
     */
    public function getFragments() : array
    {
        return [
            ...$this->constants,
            ...$this->properties,
            ...$this->methods,
        ];
    }

    public function addConstant(
        string     $name,
        mixed      $value,
        Visibility $visibility = Visibility::PUBLIC,
        ?string    $comment = null,
        ?string    $type = null,
    ) : self {
        $this->constants[$name] = new PhpConstant( $name, $value, $visibility, $comment, $type );
        return $this;
    }

    public function addProperty( string $name, mixed $value ) : self
    {
        $this->properties[$name] = $value;
        return $this;
    }

    public function addMethod(
        string            $name,
        string|Stringable $code,
        string            $arguments = '',
        string            $returns = 'void',
        Visibility        $visibility = Visibility::PUBLIC,
        bool              $final = false,
        ?string           $comment = null,
    ) : self {
        $this->methods[$name] = new PhpMethod(
            $name,
            (string) $code,
            $arguments,
            $returns,
            $visibility,
            $final,
            $comment,
        );
        return $this;
    }
}
