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
//$e->AverageInHour(0);
$e->AverageInDay();

?>
