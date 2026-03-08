<?php
require __DIR__ . '/../vendor/autoload.php';

$tmp = tempnam(sys_get_temp_dir(), 'test') . '.xlsx';
$w = new OpenSpout\Writer\XLSX\Writer();
$w->openToFile($tmp);
$sheet = $w->getCurrentSheet();
echo method_exists($sheet, 'setColumnWidth') ? "setColumnWidth: YES\n" : "setColumnWidth: NO\n";
echo method_exists($sheet, 'setColumnWidthForRange') ? "setColumnWidthForRange: YES\n" : "setColumnWidthForRange: NO\n";
$w->close();
unlink($tmp);

$s = new OpenSpout\Common\Entity\Style\Style();
echo method_exists($s, 'setCellAlignment') ? "setCellAlignment: YES\n" : "setCellAlignment: NO\n";
echo method_exists($s, 'setFontColor') ? "setFontColor: YES\n" : "setFontColor: NO\n";
echo method_exists($s, 'setBackgroundColor') ? "setBackgroundColor: YES\n" : "setBackgroundColor: NO\n";
echo class_exists('OpenSpout\Common\Entity\Style\CellAlignment') ? "CellAlignment class: YES\n" : "CellAlignment class: NO\n";
