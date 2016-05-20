<?php

namespace ClassLoader;

/**
 * Created by Johan Mulder <johan@cambrium.nl>
 * Date: 2016-05-20 14:27
 */
class CircularDependencyException extends \Exception
{
    /**
     * CircularDependencyException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}