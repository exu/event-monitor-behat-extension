<?php
namespace EDP\EventMonitorExtension\Tests\Writer;

use EDP\EventMonitorExtension\Writer\Csv;

class CsvTest extends \PHPUnit_Framework_TestCase
{
    protected $filename = '/tmp/csv-test-file.csv';
    protected $fileCleaned = 'false';

    protected function setUp()
    {
        `rm {$this->filename}`;
    }

    public function data()
    {
        return [
            ["ab", 2133, "Maciek"],
            ["cd", 253, "Faciek"],
            ["ef", 26713133, "Ci ,apak"],
            ["gh", 12.33, "Chra pak"],
            ["ij", 219.33, "Fumfel"],
            ["kl", 533, "Łosiu"],
        ];
    }

    /**
     * @dataProvider data
     */
    public function testWriting($id, $number, $nick)
    {
        // cleaning test file
        `echo '' > {$this->filename}`;

        $csv = new Csv($this->filename);
        $input = [$id, $number, $nick];
        $csv->write($input);

        $data = str_getcsv(file_get_contents($this->filename));
        $data[0] = trim($data[0]); //getcsv doesnt remove first breakline

        $this->assertEquals($data, $input);
    }

    public function testWritingMultipleLines()
    {
        $csv = new Csv($this->filename);
        $input = [
            ["a", "b"],
            ["c", "d"],
            ["e", "f"],
            ["g", "h"],
        ];


        fwrite(STDERR, var_export($input, 1) . "\n");
        $csv->write($input);

        if (($handle = fopen($this->filename, "r")) !== false) {
            while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }

        $this->assertEquals($data, $input);
    }
}
