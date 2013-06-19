<?php
/**
 * @author Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace EDP\EventMonitorExtension\Writer;

class Csv extends AbstractWriter implements WriterInterface
{
    public function write(array $data)
    {
        $fp = fopen($this->fileName, 'a+');
        fputcsv($fp, $data);
        return fclose($fp);
    }
}
