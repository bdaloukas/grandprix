<?php

require( 'config.php');
connectdb();

createusers( 'testg', 'Δοκιμαστικό Γυμνάσιο', 11, 28);
createusers( 'testl', 'Δοκιμαστικό Λύκειο', 12, 28);

//update grd_users SET pw=1000000*rand() where pw < 100000
//update grd_users SET password=md5(pw)

function createusers( $username, $name, $gamenumber, $count)
{
	global $CFG;

	for( $i=1; $i <= $count; $i++)
	{
		$u = $username.$i;
		$sql = "SELECT * FROM {$CFG->prefix}users WHERE username=\"$u\"";
		$recs = mysql_query( $sql) or die("MySQL Query Error: ".mysql_error().' '.$sql);
		$rec = mysql_fetch_assoc( $recs);
		if( $rec != false)
			continue;
		$sql = "INSERT INTO {$CFG->prefix}users(username,name,gameid) VALUES (\"$u\",\"$name$i\",$gamenumber)";
		mysql_query( $sql) or die("MySQL Query Error: ".mysql_error().' '.$sql);
	}
}
