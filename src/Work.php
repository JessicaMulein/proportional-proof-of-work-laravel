<?php

namespace JessicaMulein\LaravelProportionalProofOfWork;


class Work
{
    const VERSION = 0.1;
    const HASH_SIZE = 256;
    const MAX_BITS = 255; // 256 - 1: at least one bit

    /**
     * @var float
     */
    protected $version;

    /**
     * @var int
     */
    protected $bits;

    /**
     * @var int
     */
    protected $date;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var string
     */
    protected $rand;

    /**
     * @var string
     */
    protected $counter;

    public function __construct($bits, string $resource)
    {
        // start with generated defaults
        $this->version = static::VERSION;
        $this->date = time();
        $this->rand = null; // cause it to be filled at work time
        $this->counter = null; // will be computed when work is done

        // set given values
        $this->bits = intval($bits);
        $this->resource = trim($resource);

        // validate
        if (!is_numeric($bits) || ($this->bits < 1) || ($this->bits > 255)) {
            throw new InvalidHashException('Invalid value for bits: ' . $bits);
        } else if (strlen($resource) < 1) {
            throw new InvalidHashException('Invalid value for resource: ' . $resource);
        }
    }

    /**
     * @param string $workString
     * @param bool $ignoreCounter
     * @return static
     * @throws IncompatibleHashVersionException
     * @throws InvalidHashException
     */
    public static function fromString(string $workString, $ignoreCounter = false)
    {
        // build an array with a guaranteed 6 elements, defaulted null for any missing
        $workArray = array_pad(explode(':', $workString, 6), 6, null);

        // start a new model with bits and resource filled in (also validates those attributes)
        $work = new static(intval($workArray[1]), trim($workArray[3]));

        // coerce numbers / set instance variables to provided values
        $work->version = floatval($workArray[0]);
        $work->date = intval($workArray[2]);
        $work->rand = !is_null($workArray[4]) ? trim($workArray[4]) : null;
        $work->counter = !is_null($workArray[5]) ? trim($workArray[5]) : null;

        // validate, including some pre-coerced values
        if (!is_numeric($workArray[0])) {
            throw new InvalidHashException('Invalid value for version: ' . $workArray[0]);
        } else if (!static::checkVersionCompatibility($work->version)) { // after initial sanity check
            throw new IncompatibleHashVersionException($work->version);
        } else if (!is_numeric($workArray[2]) || ($work->date < 0)) {
            throw new InvalidHashException('Invalid value for date: '.$workArray[2]);
        } else if (!is_string($workArray[4]) || (strlen($work->rand) < 1) || !preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $work->rand)) {
            throw new InvalidHashException('Invalid value for rand: '.$workArray[4]);
        } else if (!$ignoreCounter && (!is_string($workArray[5]) || (strlen($work->counter) < 1) || !preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $work->counter))) {
            throw new InvalidHashException('Invalid value for counter: '.$workArray[5]);
        }

        return $work;
    }

    public function __toString()
    {
        return sprintf('%f:%d:%d:%s:%s:%s',
            $this->version,
            $this->bits,
            $this->date,
            $this->resource,
            $this->rand,
            $this->counter
        );
    }

    public function getBits()
    {
        return $this->bits;
    }


    protected static function checkVersionCompatibility($version)
    {
        $version = floatval($version);

        // this version is currently backwards compatible
        return true;
    }

    protected static function testBits($binaryHash)
    {
        if (strlen($binaryHash) !== (static::HASH_SIZE/8)) {
            return false;
        }

        $convertedHash = implode('', array_map(function($c) {
            return str_pad(base_convert(ord($c), 10, 2), 8, '0', STR_PAD_LEFT);
        }, str_split($binaryHash)));

        // now we have a binary string 000....110
        // strip off the leading zeroes only and get the new string length
        return strlen($convertedHash) - strlen(ltrim($convertedHash, '0'));
    }

    protected static function verifyBits($binaryHash, $bits, &$numZeroes = null)
    {
        $numZeroes = static::testBits($binaryHash);

        // verify the number of leading zeroes are at least as many as requested
        return ($numZeroes >= $bits);
    }

    public function isValid()
    {
        $hash = hash('sha'.static::HASH_SIZE, $this->__toString(), true);
        // verify number of zeroes at left
        return static::verifyBits($hash, $this->bits);
    }

    /**
     * (re-)work the dataset
     * @return string
     */
    public function work()
    {
        // generate random bytes if none were provided
        if (empty($this->rand)) {
            $this->rand = base64_encode(random_bytes(static::HASH_SIZE/8));
        }

        // start the counter at zero
        $counter = 0;
        do
        {
            $this->counter = base64_encode(strval($counter));
            // increment the counter for the next pass (if required)
            $counter++;
        } while (!$this->isValid());

        return $this->__toString();
    }
}