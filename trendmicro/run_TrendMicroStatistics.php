<?php
// sample command
// php run_TrendMicroStatistics.php -TB US_record -o query25.txt
require_once("TrendMicroStatistics.php");
$para = ParameterParser($argc, $argv);
$obj = new QueryStatistics($para);
$obj->run();
?>
