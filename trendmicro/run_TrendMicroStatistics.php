<?
// sample command
// php TrendMicroStatistics.php -TB US_record -o query.txt
require_once("TrendMicroStatistics.php");
$para = ParameterParser($argc, $argv);
$obj = new QueryStatistics($para);
$obj->run();
