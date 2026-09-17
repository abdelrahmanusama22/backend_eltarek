<?php
require 'vendor/autoload.php';

$path = 'test.xlsx';

// Create a dummy xlsx using Spout
$writer = new \OpenSpout\Writer\XLSX\Writer();
$writer->openToFile($path);
$row = \OpenSpout\Common\Entity\Row::fromValues(['ID', 'Name', 'Price']);
$writer->addRow($row);
$row2 = \OpenSpout\Common\Entity\Row::fromValues(['1', 'Trim A', '200000']);
$writer->addRow($row2);
$writer->close();

echo "Created test.xlsx\n";

$csvPath = 'test.csv';
$reader = new \OpenSpout\Reader\XLSX\Reader();
$reader->open($path);

$writer = new \OpenSpout\Writer\CSV\Writer();
$writer->openToFile($csvPath);

foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) {
        $writer->addRow($row);
    }
    break; // Only read the first sheet
}

$writer->close();
$reader->close();

echo "Converted to test.csv\n";
echo file_get_contents($csvPath);