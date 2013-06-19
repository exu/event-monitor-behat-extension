<?php
/**
 * @author Jacek Wysocki <jacek.wysocki@gmail.com>
 */
namespace EDP\EventMonitorExtension\Writer;

class Csv extends AbstractWriter implements WriterInterface
{
    protected function arrayDimension(array $data)
    {
        return is_array(reset($data)) ?  $this->arrayDimension(reset($data)) + 1 : 1;
    }

    public function write(array $data)
    {
        $fp = fopen($this->fileName, 'a');

        switch ($this->arrayDimension($data)) {
            case 1:
                fputcsv($fp, $data);
                break;
            case 2:
                foreach ($data as $row) {
                    fputcsv($fp, $row);
                }
                break;
            default:
                throw new \Exception('Invalid input data array dimension');
        }

        return fclose($fp);
    }
}
