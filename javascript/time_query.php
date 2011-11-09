<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Time Query</title>
	</head>
	<body>
		<form action="time_chart.php" method="post" target="_blank" accept-charset="utf-8">
		<table>
		<tr>
			<td>query</td>
			<td><input type="text" name="query" value="" id="query" maxlength="100" size="50" style="width:50%"  /><br /></td>
		</tr>
		<tr>
			<td>url</td>
			<td><input type="text" name="url" value="" id="url" maxlength="100" size="50" style="width:50%"  /><br /></td>
		</tr>
		<tr>
			<td>special where clause</td>
			<td><input type="text" name="sp" value="" id="sp" maxlength="100" size="50" style="width:50%"  /><br /></td>
		</tr>
		<tr>
			<td>whole sql</td>
			<td><textarea rows="10"  name="sql" id="sql" cols="50" /></textarea></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" name="mysubmit" value="Submit Post!"  /></td>
		</tr>
		</table>
		</form>
	</body>
</html>
