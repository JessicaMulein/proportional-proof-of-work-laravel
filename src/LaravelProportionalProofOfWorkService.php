<?php

namespace JessicaMulein\LaravelProportionalProofOfWork;


use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class LaravelProportionalProofOfWorkService
{
    public function parseMiddlewareOptions(array $options)
    {
        $optArray = [];
        foreach($options as $v) {
            $v = trim($v);
            if (empty($v)) continue;

            $parts = explode('=', $v, 2);
            $parts[0] = trim($parts[0]);
            if (empty($parts[0])) continue;

            if (isset($parts[1])) {
                $parts[1] = trim($parts[1]);

                if (strlen($parts[1]) == 0) {
                    $parts[1] = true; // default true
                } else if (strtolower($parts[1]) == 'true') {
                    $parts[1] = true;
                } else if (strtolower($parts[1]) == 'false') {
                    $parts[1] = false;
                } else {
                    // assume int
                    $parts[1] = intval($parts[1]);
                }
            } else {
                $parts[1] = true;
            }

            $optArray[trim($parts[0])]=$parts[1];
        }
        return $optArray;
    }

    public function requirePpow(Request $request, $options)
    {
        if ($request->hasHeader('x-ppow')) {

        }
    }
}