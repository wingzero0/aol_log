<?php
// this program want to additional url into `uid_clean` 
// (the uid_clean table orginially created by `url_clean` TB with todo)
// We just enlarge the url set, then we can crawl the url's corresponding webpage 
// to train our doc model

include_once("kit_lib.php");


$para = ParameterParser($argc, $argv);

//source table
if (!isset($para["TB"])){
	$para["TB"] = "tmp";
}

//uid table
if (!isset($para["UTB"])){
	$para["UTB"] = "uid_clean";
}

$database_cnn = "b95119";
mysql_select_db($database_cnn,$b95119_cnn);

$sql = sprintf("
	SELECT  distinct `url` 
	FROM  `%s` 
	WHERE  `url` NOT 
	IN (
		SELECT  `url` 
		FROM  `%s`
	)", 
	$para["TB"], $para["UTB"]);

$result = mysql_query($sql) or die($sql."\n".mysql_error());

while ($row = mysql_fetch_row($result)){
	$counter = 0;
	$url = preg_replace("/'/", "\\\'", $row[0]);
	$sql = sprintf(
		"insert into `%s` (`url`)
		values ('%s')",
			$para["UTB"],$url);
	mysql_query($sql) or die($sql."\n".mysql_error());
}
?>
