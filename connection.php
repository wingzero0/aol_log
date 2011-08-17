<?php
# how can I put this file to github but no one can view my password?
# specially, how can I remove the password in the history?
$hostname_cnn = "localhost";
$database_cnn = "XXXX";
$username_cnn = "XXXX";
$password_cnn = "XXXX";
$b95119_cnn = mysql_pconnect($hostname_cnn, $username_cnn, $password_cnn) or trigger_error(mysql_error(),E_USER_ERROR);
mysql_query("SET NAMES utf8");
?>
