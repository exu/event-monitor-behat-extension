<?php
/**
 * @author Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace EDP\EventMonitorExtension\Writer;

abstract class AbstractWriter
{
    protected $fileName;

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    abstract public function write(array $data);
}
