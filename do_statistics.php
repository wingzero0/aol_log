<?php
/* this program will get the statistics of AOL 24hour timestamp log
 * under input condition.
 * it alos list the url which clicks is similar with other with same query.
 * if two urls clicked by the same query in a specific time t, 
 * and the clicks count differentis lower than 3, they will be consider as 
 * similar. If all day the urls are similar, the program will find it out.
 * (they should not be ranking in our model)
 * input format: php do_statistics.php -TB table_name [-low lower_bound] [-up upper_bound]
 */

include "statistics.php";
$para = ParameterParser($argc, $argv);

//$s = new Statistics($para);
//$s->CountQueryURLPair();
//
//$f = new NavieCluster($para);
//$f->ClusterUrlPairs();
//$f->displaySimilar();
$e = new Entropy($para);

// nonspecific query entropy in day ------------------------------------------
$stdout_flag = false;
if (isset($para["o"])){
	$fp = fopen($para["o"].".csv", "w");
}else{
	$fp = NULL;
}
if ($fp == NULL){
	printf("the output will print to the stdout\n");
	$stdout_flag = true;
	$fp = STDOUT;
}
$ret = $e->NonSpecificQ_DistributionInDay();
for ($i = 0.00; $i< 3.00; $i += 0.01){
	$string_entropy = sprintf("%.2f", $i);
	if (!isset($ret["distribution"][$string_entropy])){
		fprintf($fp, "%s\t%lf\n", $string_entropy, 0);
	}else{
		fprintf($fp, "%s\t%lf\n", $string_entropy, $ret["distribution"][$string_entropy]["prob"]);
		print_r($ret["distribution"][$string_entropy]);
	}
}
if ($stdout_flag == false){
	fclose($fp);
}
// ------------------------------------------

// entropy in day ------------------------------------------
/*
$stdout_flag = false;
if (isset($para["o"])){
	$fp = fopen($para["o"].".csv", "w");
}else{
	$fp = NULL;
}
if ($fp == NULL){
	printf("the output will print to the stdout\n");
	$stdout_flag = true;
	$fp = STDOUT;
}
$ret = $e->DistributionInDay();
for ($i = 0.00; $i< 3.00; $i += 0.01){
	$string_entropy = sprintf("%.2f", $i);
	if (!isset($ret["distribution"][$string_entropy])){
		fprintf($fp, "%s\t%lf\n", $string_entropy, 0);
	}else{
		fprintf($fp, "%s\t%lf\n", $string_entropy, $ret["distribution"][$string_entropy]["prob"]);	
	}
}
if ($stdout_flag == false){
	fclose($fp);
}
 */
// ------------------------------------------


/*
// entropy in hour ------------------------------------------
for ($t = 0;$t<24;$t++){
	$stdout_flag = false;
	if (isset($para["o"])){
		$fp = fopen($para["o"].".".$t.".csv", "w");
	}else{
		$fp = NULL;
	}
	if ($fp == NULL){
		printf("the output will print to the stdout\n");
		$stdout_flag = true;
		$fp = STDOUT;
	}
	$ret = $e->DistributionInHour($t);
	for ($i = 0.00; $i< 3.00; $i += 0.01){
		$string_entropy = sprintf("%.2f", $i);
		if (!isset($ret["distribution"][$string_entropy])){
			fprintf($fp, "%s\t%lf\n", $string_entropy, 0);
		}else{
			fprintf($fp, "%s\t%lf\n", $string_entropy, $ret["distribution"][$string_entropy]["prob"]);	
		}
	}
	if ($stdout_flag == false){
		fclose($fp);
	}
}
// ------------------------------------------
*/
?>
