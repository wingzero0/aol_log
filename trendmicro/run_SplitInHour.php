<?php
// sample command
// php run_SplitInHour.php -TB us_24 -qf query.txt
require_once("SplitInHour.php");
$para = ParameterParser($argc, $argv);
$obj = new QuerySplitInHour($para);
$obj->run();
?>