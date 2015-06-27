<?php

require( 'config.php');
require( 'lib.php');
connectdb();

if( array_key_exists( 'username', $_POST))
    OnLogin2( 'admin.php');

require_login( 'admin.php');

$result = mysql_query( "SELECT * FROM {$CFG->prefix}users WHERE id=$userid");
$user = mysql_fetch_assoc( $result);
if( $user[ 'userlevel'] != 1)
{
    echo GetHeader('login');
    die( 'Ο χρήστης δεν έχει δικαίωμα σε αυτή την οθόνη');
}

    if( !array_key_exists( 'question', $_POST))
    {
        DoSelectQuestion();
        die;
    }
    
    $action = $_POST[ 'action'];
    if( $action == 'startquestion')
    {
		if( array_key_exists( 'show', $_POST))
			DoStartQuestion( false, false);
		else
			DoStartQuestion( true, false);
        die;
    }

    if( $action == 'starttimer')
	{
		OnStartTimer();
		die;
	}
	

    if( $action == 'gradeanswers')
    {
		OnGradeAnswers();
        die;
    }
    
    ShowAnswers();
    
    function ShowAnswers()    
    {
        global $CFG;
                        
        $selecthitid = "(SELECT MAX(id) FROM {$CFG->prefix}hits h WHERE h.userid=u.id AND kind=0)";
        $query = "SELECT u.id, u.name, (SELECT answer FROM {$CFG->prefix}hits WHERE id=$selecthitid) as answer".
                    " FROM {$CFG->prefix}users u".
                    " ORDER BY username";
                    
         $result = mysql_query( $query);

        echo '<table border=1>';         
        while ($row = mysql_fetch_assoc( $result)) 
        {
            echo '<tr>';
            echo '<td>'.$row['username'].'</td>';
            echo '<td>'.$row['name'].'</td>';
            echo '<td>'.$row['answer'].'</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    
    function DoStartQuestion( $allowanswers, $retrycorrect)
    {
        echo GetHeader( 'Εκκίνηση ερώτησης');
        
        $question = $_POST[ 'question'];

		if( array_key_exists( 'duration', $_POST))
	        $duration = $_POST[ 'duration'];
		else
			$duration = 0;
        
		if( !$allowanswers)
			$duration = 0;

        $questiontext = CopyNewQuestion( $question, $duration);
		echo '<table border=0>';
        echo '<tr><td>Ερώτηση:</td><td>'.$question.'</td></tr>';
        echo '<form name="MainForm" method="post" action="admin.php">';
      
        echo '<input name="question" type="hidden" id="question" value="'.$question.'"> ';

		$grade = GetParamPost( 'grade');

		if( $allowanswers or $retrycorrect)
		{
?>
        	<input name="action" type="hidden" id="action" value="gradeanswers">
<tr><td>Σωστή απάντηση:</td><td> 
<select name="correct">
  <option value="0"></option>
  <option value="1">1</option>
  <option value="2">2</option>
  <option value="3">3</option>
  <option value="4">4</option>
</select></td></tr>
	        
	        <tr><td>Βαθμοί:</td><td><input name="grade" type="text" id="grade" value=<?php echo $grade; ?>></td></tr>
	        <tr><td colspan=4><input type="submit" value="Καταχώρηση"></td></tr>
<?php
		}else
		{
		    $duration = GetParamPost( 'duration');
		    
			echo '<input name="action" type="hidden" id="action" value="starttimer">';
    		    
            echo '<tr><td>Βαθμοί: </td>';
            echo '<td><input name="grade" type="text" id="grade" value='.$grade.'></td></tr>';
            
		    echo '<tr><td>Χρόνος: </td>';
    		echo '<td><input name="duration" type="text" id="duration" value='.$duration.'> sec</td></tr>';
    					
	        echo '<tr><td colspan=3><input type="submit" value="Εκκίνηση χρόνου"></td></tr>';
		}
?>
	<script type="text/JavaScript">
		document.forms['MainForm'].elements['grade'].focus();
	</script>
<?php	
        echo '</table>';
        echo '</form><br><br>';

        echo "<iframe src=\"timeradmin.php?userid=0\" width=\"100%\" id=\"timerframe\"></iframe>";        
        
        echo "<div id=\"questionframe\">$questiontext</div>";        
?>               
    <script type="text/JavaScript">
    function timedRefresh(timeoutPeriod) {
    	setTimeout("OnTimer();",timeoutPeriod);
    }
    
    function OnTimer()
    {
        var f = document.getElementById('timerframe');
        f.src = f.src;
    
        timedRefresh(1000);
    }
    timedRefresh(1000);
    </script>
 <?php   
    
    }
    
    function CopyNewQuestion( $question, $duration)
    {
        global $CFG, $gameid;

	$result = mysql_query( "SELECT dir FROM {$CFG->prefix}game WHERE id=$gameid") or die("MySQL Query Error: ".mysql_error());
	$game = mysql_fetch_assoc( $result);
        
        $file =  $CFG->inputdir.'/'.$game[ 'dir'].'/'.$question.'/index.htm';

        $questiontext = file_get_contents( $file);

		//Αυτόματη αλλαγή κωδικοποίησης
		if (!is_UTF8( $questiontext))
		{
			//Δεν είναι utf8
			$pos = strpos( $questiontext, 'charset=windows-1253');
			if( $pos)
			{
				$questiontext = iconv('windows-1253', "UTF-8", $questiontext);
				$questiontext = str_replace( 'windows-1253', 'utf8', $questiontext);
			}
		}
             
        $timefinish = time() + $duration;
                               
		$md5 = md5( $questiontext);
        $sql = "UPDATE {$CFG->prefix}game ".
		" SET question=$question,duration=$duration,timefinish=$timefinish,questiontext=".
		'"'.mysql_real_escape_string( $questiontext).'",md5questiontext="'.$md5.'"'.
		" WHERE id=$gameid";
        mysql_query( $sql);
        
        return $questiontext;
    }

// Returns true if $string is valid UTF-8 and false otherwise.
	function is_utf8($string) 
	{
    // From http://w3.org/International/questions/qa-forms-utf-8.html
    return preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $string);

} // function is_utf8

	function OnStartTimer()
	{
	    global $CFG;

	    $duration = $_POST[ 'duration'];

            $timefinish = time() + $duration;

            $sql = "UPDATE {$CFG->prefix}game SET duration=$duration,timefinish=$timefinish WHERE id=1";
            mysql_query( $sql);

	    DoStartQuestion( true, false);
	}
    
    
  function DoSelectQuestion()
  {
    echo GetHeader( 'Επιλογή ερώτησης');
    
    $grade = GetParamPost( 'grade');
    $question = GetParamPost( 'question');
    
    echo '<table cellpadding=0 border=0>';
    echo '<form name="MainForm" method="post" action="admin.php">';
  
    echo '<tr><td>Ερώτηση:</td>';
    echo '<td><input name="question" type="text" id="question" value="'.$question.'"> ';
    echo '</td></tr>';
	
    echo '<tr><td>Βαθμοί: </td>';
    echo '<td><input name="grade" type="text" id="grade"></td></tr>';

    echo '<tr><td>Χρόνος:</td>';
    echo '<td><input name="duration" type="text" id="duration" width="5"> sec</td></tr>';
        
    echo '<input type="hidden" name="action" id="action" value="startquestion">';
    echo '<tr><td></td><td><center><br>';
	echo ' <input type="submit" name="show" value="Μόνο εμφάνιση">';
	echo '<input type="submit" name="submit" value="Εκκίνηση"></td>';
    echo '</table>';
    echo '</form>';
?>
	<script type="text/JavaScript">
		document.forms['MainForm'].elements['question'].focus();
	</script>
<?php
  }
 
function OnGradeAnswers()
{
    global $CFG;
    
    $question = $_POST[ 'question'];
    $correct = $_POST[ 'correct'];
    $grade = $_POST[ 'grade'];

	if( ($correct == 0) or( $grade == 0))
	{
		DoStartQuestion( false, true);
		die;
	}
    
    $result = mysql_query( "SELECT * FROM {$CFG->prefix}questions WHERE question=$question");
    $rec = mysql_fetch_assoc( $result);
    if( $rec === false)
    {
        $query = "INSERT INTO {$CFG->prefix}questions(question) SELECT $question";
        mysql_query( $query);
    }
    
    $query = "UPDATE {$CFG->prefix}questions SET correct=$correct,grade=$grade WHERE question=$question";
    mysql_query( $query);
    
    
    $sql = "UPDATE {$CFG->prefix}hits SET grade=0 WHERE question=$question AND answer<>$correct";
    mysql_query( $sql);

    $sql = "UPDATE {$CFG->prefix}hits SET grade=$grade,graded=1 WHERE question=$question AND answer=$correct";
    mysql_query( $sql);    
    
    DoSelectQuestion();
}

