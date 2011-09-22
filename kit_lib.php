<?php
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

require_once("connection.php");
?>