<?php

namespace JessicaMulein\LaravelProportionalProofOfWork;

use Throwable;

class IncompatibleHashVersionException extends \Exception
{
    public function __construct($version, $code = 0, Throwable $previous = null)
    {
        parent::__construct('Incompatible hash version: '.$version, $code, $previous);
    }
}