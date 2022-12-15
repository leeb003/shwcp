<?php

//declare(strict_types=1);

//use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\PHPOffice\Set\PHPOfficeSetList;
use Rector\Config\RectorConfig;

//return static function (RectorConfig $rectorConfig): void {
//    $rectorConfig->sets([
//        PHPOfficeSetList::PHPEXCEL_TO_PHPSPREADSHEET
//    ]);
//};

return static function (RectorConfig $rectorConfig): void {
	//$rectorConfig->import(PHPOfficeSetList::PHPEXCEL_TO_PHPSPREADSHEET);
	$rectorConfig->sets([
        PHPOfficeSetList::PHPEXCEL_TO_PHPSPREADSHEET
    ]);
//    $rectorConfig->paths([
//        __DIR__ . '/PHPExcel',
//        __DIR__ . '/mail-chimp',
//		__DIR__ . '/tgmpa',
//		__DIR__
//]);
};
//	$rectorConfig->skip([
       // __DIR__ . '/PHPExcel/**/*',
		//__DIR__ . '/vendor/**/*',
		//__DIR__ . '/mail-chimp/**/*',
		//__DIR__ . '/tgmpa/**/*',
//    ]);
    // register a single rule
//    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    //    $rectorConfig->sets([
    //        LevelSetList::UP_TO_PHP_81
    //    ]);
//};
