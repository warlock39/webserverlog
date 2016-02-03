<?php

namespace AppBundle\WebServerLog\Exception;

/**
 * Class WebServerLogException.
 */
class WebServerLogException extends \Exception
{
    /**
     * @param \SplFileInfo $file
     *
     * @return WebServerLogException
     */
    public static function notLogFile($file)
    {
        return new self("File '{$file->getFilename()}' is not log file");
    }

    /**
     * @param \SplFileInfo $file
     *
     * @return WebServerLogException
     */
    public static function notReadable($file)
    {
        return new self("File '{$file->getFilename()}' is not readable");
    }

    /**
     * @param string $operator
     * @return WebServerLogException
     */
    public static function unknownFilterOperator($operator)
    {
        $stringOperator = self::toString($operator);
        return new self('Unknown filter operator '.($stringOperator ? "'$stringOperator'" : ''));
    }

    /**
     * @param string $name
     * @return WebServerLogException
     */
    public static function invalidFilterValue($name)
    {
        $nameStr = self::toString($name);
        return new self('Filter "'.$nameStr.'" has not valid value. Scalar or array of scalar expected"');
    }

    /**
     * @param string $name
     * @return WebServerLogException
     */
    public static function oneDimensionName($name)
    {
        $nameStr = self::toString($name);
        return new self('Filter "'.$nameStr.'" must have only one dimension filter name"');
    }

    /**
     * @return WebServerLogException
     */
    public static function invalidQbAlias()
    {
        return new self("Can't create Filter due to invalid QueryBuilder alias. Only one alias expected");
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function toString($value)
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
