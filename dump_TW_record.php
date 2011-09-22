<?php
/* this program calculate the probability of the url1 to url2
 */  


include("connection.php");
mysql_select_db($database_cnn,$b95119_cnn);

$tb_name = $argv[1];
$outfile = $argv[2];

$fp = fopen($outfile, "w");

if ($fp == null){
	printf("%s can't be write",$outfile);
	return 0;
}
$query = sprintf("select * from `%s`",$tb_name);

mysql_query($query) or die(mysql_error());

$result = mysql_query($query) or die(mysql_error());
while($row = mysql_fetch_row($result)) {
	$total_column = count($row);
	for ($i = 0; $i< $total_column - 1; $i++){
		if (!empty($row[$i])){
			fprintf($fp, "%s\t", $row[$i]);
		}else{
			fprintf($fp, "NULL\t", $row[$i]);
		}
	}
	if (!empty($row[$i])){
		fprintf($fp, "%s\n", $row[$i]);
	}else{
		fprintf($fp, "NULL\n", $row[$i]);
	}
}

fclose($fp);
?>
