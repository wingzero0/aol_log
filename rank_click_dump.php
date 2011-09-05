<?php
/* this program will dump the aol_log db table with a specific query.
 * you can specific the lower bound of click of the corresponding url.
 * para
 * -TB table name of the aol_log db
 * -query the specific query
 * -low the lower bound of the click count, default is 10
 * -t time t which affected by the lower bound
 * sample command line:
 * php mysql_dump.php -TB smooth_0.0.set.1.train -query prom\ dresses -t 15 -low 10
 */
include_once("connection.php");
mysql_select_db($database_cnn,$b95119_cnn);

$para = ParameterParser($argc, $argv);

$para = DefaultParameter($para);

$query = sprintf("
	select * from `%s.rank` where `query` = '%s' 
	", $para["TB"], $para["query"]);

$result = mysql_query($query);
if (mysql_error()){
	fprintf(STDERR,"query = %s\n%s\n", $para["query"], mysql_error());
}


//echo $query."\n";
$urls = array();
while ($row = mysql_fetch_row($result)){
	//print_r($row);
	$save_row[] = $row;
	/*
	for ($i = 0;$i < count($row); $i++){
		echo $row[$i]."\t";
	}
	echo "\n";
	*/
	$urls[] = $row[2];
}

echo "count\n";
for ($j = 0; $j < count($urls);$j++){
	$query = sprintf("
		select * from `%s` where `query` = '%s' and `url` = '%s' 
		", $para["TB"], $para["query"], $urls[$j]);
	
	$result = mysql_query($query);
	//echo $query."\n";
	if (mysql_error()){
		fprintf(STDERR,"query = %s\n%s\n", $para["query"], mysql_error());
	}
	if ($row = mysql_fetch_row($result)){
		//print_r($row);
		for ($i = 0;$i < count($row); $i++){
			echo $row[$i]."\t";
		}
		echo "\n";
	}
}

echo "rank\n";
foreach ($save_row as $j => $row){
	for ($i = 0;$i < count($row); $i++){
		echo $row[$i]."\t";
	}
	echo "\n";
}
function DefaultParameter($para){
	$new_para = array();
	if (!isset($para["low"])) {
		$new_para["low"] = "0";
	}
	if (!isset($para["t"])) {
		$new_para["t"] = "15";
	}
	foreach ($para as $i => $value){
		$new_para[$i] = $para[$i];
	}
	return $new_para;
}

function ParameterParser($argc, $argv){
	$para = array();
	for ($i = 0; $i< $argc - 1; $i++){
		$ret = preg_match("/^-(.*)/", $argv[$i], $match);
		if ($ret == 1){
			$para[$match[1]] = $argv[$i+1];
			$i = $i +1;
		}
	}
	return $para;
}
?>
