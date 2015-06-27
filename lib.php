<?php

function connectdb()
{
    global $CFG;
    
    mysql_connect( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass);
    mysql_select_db( $CFG->dbname);
    mysql_query("set names utf8");
}

  function GetHeader( $title)
  {
    $s = "";
    $s .= "<HTML> <head>";
  	$s .= "<title>$title</title>";
	  $s .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    $s .= "</head>\n";
    
    return $s;
  }
    
      function require_login( $filephp)
    {
        global $userid, $gameid;

        if( !array_key_exists( 'userid', $_SESSION))
        {
            ShowFormLogin( $filephp);
            die;
        }
        $userid = $_SESSION[ 'userid'];
		$gameid = $_SESSION[ 'gameid'];
    }

function ShowFormLogin( $filephp, $msg='')
{
    echo GetHeader( 'Είσοδος');
    
    if( $msg != '')
        echo '<br><b>'.$msg.'<br><br>';
    
    echo '<table cellpadding=0 border=0>';
    echo '<form name="MainForm" method="post" action="'.$filephp.'">';
  
    echo '<tr><td>Όνομα χρήστη:</td>';
    echo '<td><input name="username" id="username" type="text" id="username"> ';
    echo '</td></tr>';
	
    echo '<tr><td>Κωδικός:</td>';
    echo '<td><input name="password" type="password" id="password"> ';
    echo '</td></tr>';
    echo '<tr><td></td><td><center><br><input type="submit" value="Είσοδος"></td>';   
    echo '</table>';
    echo '</form>';
?>
	<script type="text/JavaScript">

		document.forms['MainForm'].elements['username'].focus();
	</script>
<?php
}

  function GetParamPost( $name, $default='')
  {
    if( array_key_exists( $name, $_POST))
        return $_POST[ $name];
    else
        return '';
  }
  
  function OnLogin2( $filephp)
  {
    global $CFG, $userid, $gameid;
    
    $username = $_POST[ 'username'];
    
    echo GetHeader( '');
    
    $username = $_POST[ 'username'];
    $password = $_POST[ 'password'];
                 
    $query = "SELECT * FROM {$CFG->prefix}users WHERE username='$username'";
    $result = mysql_query($query);   
    $row = mysql_fetch_array($result);
    
    if( $row == false)
    {
        ShowFormLogin( $filephp, '<b>Λάθος όνομα χρήστη</b>');
        die;
    }
          
    if( $row[ 'password'] != '')
    {
        if( md5( $password) != $row[ 'password'])
        {
            ShowFormLogin( $filephp, '<b>Λάθος κωδικός</b>');
            die;
        }
    }

    $ip = GetMyIP();
    $hostname = gethostname();
    $userid = $row['id'];
	$gameid = $row['gameid'];
    $query = "INSERT INTO {$CFG->prefix}logins(userid,hostname,ip) SELECT $userid, '$hostname','$ip'";
    mysql_query($query);
    
    $query = "UPDATE {$CFG->prefix}users SET lastip='$ip' WHERE id=$userid";
    mysql_query($query);
    
    $_SESSION[ 'userid'] = $userid;
    $_SESSION[ 'gameid'] = $gameid;
  }

  function GetMyIP()
  {
    if( array_key_exists( "HTTP_X_FORWARDED_FOR", $_SERVER))
      return $_SERVER[ "HTTP_X_FORWARDED_FOR"];
    else
      return $_SERVER['REMOTE_ADDR'];
  }

function ComputeTimerStudent( &$resttime, &$question, &$questiontext, &$md5, &$infoanswer)
{
	global $CFG, $gameid;

	$userid = $_SESSION[ 'userid'];

	$sql = "SELECT * FROM {$CFG->prefix}game WHERE id=$gameid";
	$result = mysql_query( $sql) or die("MySQL Query Error: ".mysql_error().' '.$sql);
	$game = mysql_fetch_assoc( $result);
	$gameid = $game[ 'id'];
	$resttime = $game[ 'timefinish'] - time();

	if( $resttime < 0)
    	$resttime = 0;

	$question = $game[ 'question'];
	$questiontext = $game[ 'questiontext'];
	$md5 = $game[ 'md5questiontext'];

	$sql = "SELECT * FROM {$CFG->prefix}users WHERE id=$userid";
	$result = mysql_query( $sql) or die("MySQL Query Error: ".mysql_error().' '.$sql);
	$user = mysql_fetch_assoc( $result);
	if( $user == false)
    	die( $sql);

	$ip = GetMyIP();
	if( $user[ 'lastip'] != $ip)
    	die( 'Λάθος IP: '.$ip.' user='.$user[ 'username']);

	$sql = "SELECT * FROM {$CFG->prefix}hits WHERE question=$question AND todelete=0 AND userid=$userid";
	$result = mysql_query( $sql) or die("MySQL Query Error: ".mysql_error().' '.$sql);
	$rec = mysql_fetch_assoc( $result);
	$infoanswer = '';
	if( $rec == false)
    	$infoanswer = '<b>'.$user[ 'name'].'</b>: Δεν δώσατε ακόμη καμία απάντηση';
	else	
	{
    	$answer = $rec[ 'answer'];
    	$infoanswer = '<b>'.$user[ 'name'].'</b>: Σαν απάντηση δώσατε: <b>'.$answer.'</b>';
		if( $rec[ 'graded'])
		{
			if( $rec[ 'grade'] == 1)
				$s = '1 βαθμός';
			else
				$s = $rec[ 'grade'].' βαθμοί';
			$infoanswer .= ' Απαντήσατε σωστά ('.$s.')';
		}
	}
}

