<?php

declare(strict_types=1);

namespace Support\PhpGenerator;

use Core\Exception\ErrorException;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;
use RuntimeException;

class Argument
{
    public const string UNASSIGNED = '__UNASSIGNED_ARGUMENT__';

    /**
     *  Exports a serializable PHP value to PHP code using {@see VarExporter}.
     *
     * @param mixed $value
     *
     * @return string
     */
    final public static function export(
        mixed $value,
    ) : string {
        try {
            $argument = VarExporter::export( $value );
        }
        catch ( ExceptionInterface $exception ) {
            throw new RuntimeException(
                message  : $exception->getMessage(),
                previous : $exception,
            );
        }

        ErrorException::check();

        return $argument;
    }
}
