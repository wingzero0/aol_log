<?php
// this program want to filter out the low freq records (total click < 100 ) 
// in aol 24 format table.

include_once("kit_lib.php");


$para = ParameterParser($argc, $argv);

if (!isset($para["TB"])){
	$para["TB"] = "tmp";
}

$database_cnn = "b95119";
mysql_select_db($database_cnn,$b95119_cnn);

$sql = sprintf("CREATE  TABLE if not exists `%s_clean` (  `id` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
	`query` varchar( 128  )  COLLATE utf8_unicode_ci NOT  NULL ,
	`url` varchar( 255  )  COLLATE utf8_unicode_ci NOT  NULL ,
	`0` int( 11  )  NOT  NULL ,
	`1` int( 11  )  NOT  NULL ,
	`2` int( 11  )  NOT  NULL ,
	`3` int( 11  )  NOT  NULL ,
	`4` int( 11  )  NOT  NULL ,
	`5` int( 11  )  NOT  NULL ,
	`6` int( 11  )  NOT  NULL ,
	`7` int( 11  )  NOT  NULL ,
	`8` int( 11  )  NOT  NULL ,
	`9` int( 11  )  NOT  NULL ,
	`10` int( 11  )  NOT  NULL ,
	`11` int( 11  )  NOT  NULL ,
	`12` int( 11  )  NOT  NULL ,
	`13` int( 11  )  NOT  NULL ,
	`14` int( 11  )  NOT  NULL ,
	`15` int( 11  )  NOT  NULL ,
	`16` int( 11  )  NOT  NULL ,
	`17` int( 11  )  NOT  NULL ,
	`18` int( 11  )  NOT  NULL ,
	`19` int( 11  )  NOT  NULL ,
	`20` int( 11  )  NOT  NULL ,
	`21` int( 11  )  NOT  NULL ,
	`22` int( 11  )  NOT  NULL ,
	`23` int( 11  )  NOT  NULL ,
	PRIMARY  KEY (  `id`  ) ,
	KEY  `Query` (  `query`  ) ,
	KEY  `Url` (  `url`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci",
	$para["TB"]);

mysql_query($sql) or die($sql."\n".mysql_error());

$sql = sprintf("select * from `%s` ORDER BY `query` ASC, `url` ASC ",
	$para["TB"]);

$result = mysql_query($sql) or die($sql."\n".mysql_error());

while ($row = mysql_fetch_row($result)){
	$counter = 0;
	for ($t = 0;$t < 24;$t++){
		$counter += intval($row[$t + 3]);
	}
	$query = preg_replace("/'/", "\\\'", $row[1]);
	$url = preg_replace("/'/", "\\\'", $row[2]);
	if ($counter >= 100 ){
		$sql = sprintf(
			"insert into `%s_clean` (`query`, `url`,
			`0`,
			`1`,
			`2`,
			`3`,
			`4`,
			`5`,
			`6`,
			`7`,
			`8`,
			`9`,
			`10`,
			`11`,
			`12`,
			`13`,
			`14`,
			`15`,
			`16`,
			`17`,
			`18`,
			`19`,
			`20`,
			`21`,
			`22`,
			`23`) values (
				'%s','%s',
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d,
				%d)",
				$para["TB"],$query,$url,
				$row[3],
				$row[4],
				$row[5],
				$row[6],
				$row[7],
				$row[8],
				$row[9],
				$row[10],
				$row[11],
				$row[12],
				$row[13],
				$row[14],
				$row[15],
				$row[16],
				$row[17],
				$row[18],
				$row[19],
				$row[20],
				$row[21],
				$row[22],
				$row[23],
				$row[24],
				$row[25],
				$row[26]
			);
		mysql_query($sql) or die($sql."\n".mysql_error());
	}
}
?>
