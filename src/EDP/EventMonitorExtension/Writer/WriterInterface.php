<?php
/**
 * @author Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace EDP\EventMonitorExtension\Writer;

interface WriterInterface
{
    public function write(array $data);
}
