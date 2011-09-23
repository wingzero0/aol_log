<?php
// this program want to translate the aol click through data into 24 hour format.

include_once("kit_lib.php");


$para = ParameterParser($argc, $argv);

if (!isset($para["TB"])){
	$para["TB"] = "tmp";
}

$database_cnn = "b95119";
mysql_select_db($database_cnn,$b95119_cnn);

$sql = sprintf("CREATE  TABLE if not exists `%s` (  `id` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
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

// time recognize
$database_cnn = "cikm2011";
mysql_select_db($database_cnn,$b95119_cnn);

$pattern = "/....-..-.. (..):..:../";
//$replace_pattern = "/'/";
for ($i = 1;$i<=10;$i++ ){
	$sql = sprintf(
		"select `Query`, `QueryTime`, `ClickURL` from `aol.%d.ct`",
		$i
	);
	$result = mysql_query($sql) or die($sql."\n".mysql_error());
	while ($row = mysql_fetch_row($result)){
		$ret = preg_match($pattern, $row[1], $matches);
		if ($ret == 1){
			$hour = intval($matches[1]);
			$query = preg_replace("/'/", "\\\'", $row[0]);
			$url = preg_replace("/'/", "\\\'", $row[2]);
			if (  !isset( $click[$query][$url]) ){
				$click[$query][$url]["total"] = 0;
				for ($t = 0; $t< 24;$t++){
					$click[$query][$url][$t] = 0;
				}
			}
			$click[$query][$url]["total"] += 1;
			$click[$query][$url][$hour] +=1;
		}
	}
	//print_r($click);
	//exit(0);
	break;
}


// save the record
$database_cnn = "b95119";
mysql_select_db($database_cnn,$b95119_cnn);

ksort($click);

foreach ($click as $query => $urls){
	ksort($urls);
	foreach ($urls as $url => $type){
		if ($type["total"] > 99){
			//$flag = 1;
			$sql = sprintf(
				"insert into `%s` (`query`, `url`,`0`,
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
}

echo "not finish\n";
?>
