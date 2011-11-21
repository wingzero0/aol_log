<?php
// class QuerySplitInHour want to get the query record from 'US_record' 
// in trendmicro db. it will split the record into 24 hour timestamp record.
 

include_once("trendmicro_utility.php");
mysql_select_db($database_cnn,$b95119_cnn);

class QuerySplitInHour extends TrendMicro_utility{
	public $targetTB;
	public $timeRecord;
	public $infp = null;
	public function __construct($para){
		if ( isset($para["TB"]) ){
			$this->targetTB = $para["TB"];
		}else{
			fprintf(STDERR,"please specify the target table with option \"-TB\"\n");
			exit(-1);
		}
		if ( isset($para["qf"]) ){
			$this->infp = fopen($para["qf"], "r");
			if ($this->infp == NULL){
				fprintf(STDERR,"\"%s\" can't be opened\n", $para["qf"]);
				exit(-1);
			}
		}else{
			fprintf(STDERR,"please specify the query input file with option \"-qf\"\n");
			exit(-1);
		}
		parent::__construct($para);
	}
	public function GetQuerys(){
		while (!feof($this->infp)){
			$line = fgets($this->infp);
			$line = $this->cut_last_newline($line);
			if (empty($line)){
				continue;
			}
			$list = $this->split_tab($line);
			$this->querys[intval($list[0])] = $list[1]; 
		}
	}
	public function GetRecords(){
		$database_cnn = "TrendMirco";
		mysql_select_db($database_cnn);
		foreach ($this->querys as $i => $q){
			$database_cnn = "TrendMirco";
			mysql_select_db($database_cnn);
		
			$sql = sprintf(
				"select `TIME` 
				from `US_record` where `QID` = %d",
				$i);

			$result = mysql_query($sql) or die($sql."\t". mysql_error());
			$this->timeRecord[$i] = array(); 
			for ($t = 0;$t<24;$t++){
				$this->timeRecord[$i][$t] = 0;
			}
			while($row = mysql_fetch_row($result)){
				$t = intval(gmdate("H", intval($row[0])));
				$this->timeRecord[$i][$t] +=1;
			}
			$this->InsertTimeRecord($i, $q,$this->timeRecord[$i]);
		}
	}
	public function InsertTimeRecord($QID,$q,$timeRecord){
		$database_cnn = "b95119";
		mysql_select_db($database_cnn);		
		$sql = sprintf(
			"insert into `%s` (`QID`,`Query`,
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
				%d, '%s',
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
				$this->targetTB, $QID,$q,
				$timeRecord['0'],
$timeRecord['1'],
$timeRecord['2'],
$timeRecord['3'],
$timeRecord['4'],
$timeRecord['5'],
$timeRecord['6'],
$timeRecord['7'],
$timeRecord['8'],
$timeRecord['9'],
$timeRecord['10'],
$timeRecord['11'],
$timeRecord['12'],
$timeRecord['13'],
$timeRecord['14'],
$timeRecord['15'],
$timeRecord['16'],
$timeRecord['17'],
$timeRecord['18'],
$timeRecord['19'],
$timeRecord['20'],
$timeRecord['21'],
$timeRecord['22'],
$timeRecord['23']
			);
		$result = mysql_query($sql) or die($sql."\t". mysql_error());
	}
	public function create_tb(){
		$database_cnn = "b95119";
		mysql_select_db($database_cnn);
		$sql = sprintf("CREATE  TABLE if not exists `%s` (  `QID` int( 11  )  NOT NULL,
			`Query` varchar( 128  )  COLLATE utf8_unicode_ci NOT  NULL ,
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
			PRIMARY  KEY (  `QID`  ) ,
			KEY  `Query` (  `query`  ) ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8 COLLATE  = utf8_unicode_ci",
			$this->targetTB);
		$result = mysql_query($sql) or die($sql."\t". mysql_error());
	}
	public function run(){
		$this->create_tb();
		$this->GetQuerys();
		$this->GetRecords();
	}

}
