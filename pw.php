<?php
require( 'config.php');
require( 'lib.php');

connectdb();
require_login( 'admin.php');


echo GetHeader( 'Κωδικοί');
$sql = "SELECT * FROM {$CFG->prefix}users WHERE id=$userid";
$result = mysql_query( $sql);
$user = mysql_fetch_assoc( $result);
if( $user[ 'userlevel'] != 1)
{
    echo GetHeader('login');
    die( 'Ο χρήστης δεν έχει δικαίωμα σε αυτή την οθόνη');
}

$sql = "SELECT username,name,pw FROM {$CFG->prefix}users WHERE gameid=$gameid ORDER BY username";
$recs = mysql_query( $sql);
echo '<table ellpadding=10>';
$line = 0;
while( $rec = mysql_fetch_assoc( $recs))
{
	$line++;
	if( $line % 3 == 1)
		echo '<tr width=100%><td width=33%>';
	else if( $line % 3 == 2)
		echo '</td><td width=33%>';
	else
		echo '</td><td width=33>';

	echo 'Θα μπείτε στη διεύθυνση http://192.168.1.10<br>';	
	echo 'Όνομα χρήστη: <b>'.$rec[ 'username'].'</b><br>';
	echo 'Κωδικός: <b>'.$rec[ 'pw'].'</b><br>';
	echo 'Σχολείο: <b>'.$rec[ 'name'].'<br><br>';
	
	if( $line % 3 == 0)
		echo '</tr>';
}
echo '</tr></table>';
