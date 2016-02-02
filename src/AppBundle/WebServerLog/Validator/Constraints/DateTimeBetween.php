<?php

namespace AppBundle\WebServerLog\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class DateTimeBetween extends Constraint
{
    const INVALID_RANGE_ERROR = 1;
    const INVALID_FORMAT_ERROR = 2;

    protected static $errorNames = array(
        self::INVALID_RANGE_ERROR => 'INVALID_RANGE_ERROR',
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR'
    );

    public $message = 'This value is not a valid datetime range.';
}
