<?php

namespace Support\PhpGenerator;

enum Visibility : string
{
    case PRIVATE   = 'private';
    case PROTECTED = 'protected';
    case PUBLIC    = 'public';

    public function label() : string
    {
        return \strtolower( $this->name );
    }
}
