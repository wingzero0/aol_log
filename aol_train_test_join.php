<?php
include_once("connection.php");

mysql_select_db($database_cnn,$b95119_cnn);
$sql = "select * from `smooth_0.0.set.1.train` order by `query` asc, `url` asc";
$result = mysql_query($sql) or die(mysql_error());

$click = array();

while ($row = mysql_fetch_row($result)){
	//printf("%s\t%s\t", $row[1], $row[2]);
	$query = preg_replace("/'/", "\\\'", $row[1]);
	$url = preg_replace("/'/", "\\\'", $row[2]);
	for ($t = 0;$t < 24;$t++){
		$click[$query][$url][$t] = intval($row[$t + 3]);
	}
}

$sql = "select * from `smooth_0.0.set.1.test` order by `query` asc, `url` asc";
$result = mysql_query($sql) or die(mysql_error());

while ($row = mysql_fetch_row($result)){
	$query = preg_replace("/'/", "\\\'", $row[1]);
	$url = preg_replace("/'/", "\\\'", $row[2]);
	if (  !isset( $click[$query][$url]) ){
		for ($t = 0; $t< 24;$t++){
			$click[$query][$url][$t] = 0;
		}
	}
	for ($t = 0; $t< 24;$t++){
		$click[$query][$url][$t] += intval($row[$t + 3]);
	}
}

$sql = sprintf("CREATE  TABLE if not exists `smooth_0.0.set.1.join` (  `id` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
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
	KEY  `Url` (  `url`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci");

mysql_query($sql) or die($sql."\n".mysql_error());

ksort($click);
foreach ($click as $query => $urls){
	ksort($urls);
	foreach ($urls as $url => $type){
		$sql = sprintf(
			"insert into `smooth_0.0.set.1.join` (`query`, `url`,`0`,
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
				$query,$url,
				$type[0],
				$type[1],
				$type[2],
				$type[3],
				$type[4],
				$type[5],
				$type[6],
				$type[7],
				$type[8],
				$type[9],
				$type[10],
				$type[11],
				$type[12],
				$type[13],
				$type[14],
				$type[15],
				$type[16],
				$type[17],
				$type[18],
				$type[19],
				$type[20],
				$type[21],
				$type[22],
				$type[23]
			);
		mysql_query($sql) or die($sql."\n".mysql_error());
	}
}
?>
