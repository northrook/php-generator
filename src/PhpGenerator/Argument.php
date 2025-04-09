<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;
use InvalidArgumentException;

class Argument
{
    /**
     *  Exports a serializable PHP value to PHP code using {@see VarExporter}.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function export( mixed $value ) : string
    {
        try {
            return VarExporter::export( $value );
        }
        catch ( ExceptionInterface $e ) {
            throw new InvalidArgumentException( $e->getMessage(), $e->getCode(), $e );
        }
    }
}
