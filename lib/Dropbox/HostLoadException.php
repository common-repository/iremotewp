<?php
namespace Dropbox;

/**
 * Thrown by the <code>Host::loadFromJson</code> method if something goes wrong.
 */
final class HostLoadException extends \Exception
{
    /**
     * @param string $message
     *
     * @internal
     */
    function __construct($message)
    {
        parent::__construct($message);
    }
}
