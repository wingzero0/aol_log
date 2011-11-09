<?php

include("connection.php");
mysql_select_db($database_cnn,$b95119_cnn);

function get_row(){
	$ret = array();
	$sql = "select * from `aol_24_clean` where 1";
	if ( isset($_POST["query"]) && !empty($_POST["query"])){
		$sql = sprintf("%s and `query` = '%s'", $sql, $_POST["query"]);
	}
	
	if ( isset($_POST["url"]) && !empty($_POST["url"])){
		$sql = sprintf("%s and `url` = '%s'", $sql, $_POST["url"]);
	}
	if ( isset($_POST["sp"]) && !empty($_POST["sp"])){
		$sql = sprintf("%s and %s", $sql, $_POST["sp"]);
	}
	$result = mysql_query($sql) or die($sql."<br>".mysql_error() );
	echo $sql;
	while ($row = mysql_fetch_row($result)){
		$ret[] = $row;
	}
	return $ret;	
}

$rows = get_row();
//print_r($rows);
?>

<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Highcharts Example</title>
		
		
		<!-- 1. Add these JavaScript inclusions in the head of your page -->
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
		<script type="text/javascript" src="./js/highcharts.js"></script>
		
		<!-- 1a) Optional: add a theme file -->
		<!--
			<script type="text/javascript" src="../js/themes/gray.js"></script>
		-->
		
		<!-- 1b) Optional: the exporting module -->
		<script type="text/javascript" src="./js/modules/exporting.js"></script>
		
		
		<!-- 2. Add the JavaScript to initialize the chart on document ready -->
		<script type="text/javascript">
		
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'container',
						defaultSeriesType: 'line',
						marginRight: 130,
						marginBottom: 25
					},
					title: {
						text: 'query = <?php echo $rows[1][1];?>',
						x: -20 //center
					},
					subtitle: {
						text: 'Source: WorldClimate.com',
						x: -20
					},
					xAxis: {
						categories: [
						<?php
							for ($i = 0;$i<=22;$i++){
								echo "'$i',";
							}
							echo "'23'";
						?>]
					},
					yAxis: {
						min: 0,
						title: {
							text: 'click'
						},
						plotLines: [{
							value: 0,
							width: 1,
							color: '#808080'
						}]
					},
					tooltip: {
						formatter: function() {
				                return '<b>'+ this.series.name +'</b><br/>'+
								this.x +': '+ this.y +'click';
						}
					},
					legend: {
						layout: 'vertical',
						align: 'right',
						verticalAlign: 'top',
						x: -10,
						y: 100,
						borderWidth: 0
					},
					series: [
						<?php
							foreach ($rows as $row){
						?>
						{
						name: '<?php echo $row[2]; ?>',
						data: [
							<?php 
								for ($i = 0;$i<=22;$i++){
									//echo "'".$row[$i + 3]."',";
									echo $row[$i + 3].",";
								}
								//echo "'".$row[23 + 3]."'";
								echo $row[23 + 3];
							?>]
						},
						<?php
							}
						?>					
					]
				});
				
				
			});
				
		</script>
		
	</head>
	<body>
		
		<!-- 3. Add the container -->
		<div id="container" style="width: 800px; height: 400px; margin: 0 auto"></div>
		
				
	</body>
</html>	