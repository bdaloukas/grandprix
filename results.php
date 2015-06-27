<?php

require( 'config.php');
require( 'lib.php');

connectdb();

if( array_key_exists( 'username', $_POST))
    OnLogin2();
require_login( 'results.php');

echo GetHeader( 'Αποτελέσματα');

if( array_key_exists( 'kind', $_GET))
	$s_kind = $_GET[ 'kind'];
else
	$s_kind = 2;

if( array_key_exists( 'sort', $_GET))
	$s_sort = $_GET[ 'sort'];
else
	$s_sort = '';

if( array_key_exists( 'count', $_GET))
	$s_countquestions = $_GET[ 'count'];
else
	$s_countquestions = 5;

if( array_key_exists( 'top', $_GET))
	$s_top = intval( $_GET[ 'top']);
else
	$s_top = 0;

$userid = $_SESSION[ 'userid'];
$query = "SELECT * FROM {$CFG->prefix}users WHERE id=$userid";
$result = mysql_query( $query);
$user = mysql_fetch_assoc( $result);

if( $user[ 'userlevel'] == 0)
    die( 'Δεν έχετε πρόσβαση σε αυτή την οθόνη');
    
$query = "SELECT * FROM {$CFG->prefix}game WHERE id=$gameid";
$result = mysql_query( $query);
$game = mysql_fetch_assoc( $result);

$grades = ComputeSumGrades( $katataksi);

$maxquestion = $game[ 'question'];
$question = $game[ 'question'];

$resttime = $game[ 'timefinish'] - time();

echo 'Ερώτηση: '.$game[ 'question'].' &nbsp;&nbsp;';

if( $resttime > 0)
    echo 'Υπόλοιπο χρόνου: '.$resttime.' &nbsp;';

$query = "SELECT COUNT(*) as c FROM {$CFG->prefix}users WHERE gameid=$gameid AND userlevel=0";
$result = mysql_query( $query);
$rec = mysql_fetch_assoc( $result);
$countsx = $rec[ 'c'];

$query = "SELECT COUNT(DISTINCT userid) as c FROM {$CFG->prefix}hits WHERE question=$question AND gameid=$gameid";
$result = mysql_query( $query);
$rec = mysql_fetch_assoc( $result);
echo 'Απάντησαν: '.$rec[ 'c'].' σχολεία σε σύνολο '.$countsx.' σχολείων  ';

if(( $s_top < $countsx) and ($s_top != 0))
	$countsx = $s_top; 

$number2 = floor( $countsx/2);
$number1 = $countsx - $number2;
   
if( $s_kind == 1)
	ShowSumData1( $maxquestion, $grades, $katataksi, $countsx);
//else if( $s_kind == 2)
//	ShowSumData2( $maxquestion, $result, $grades, $katataksi, $number1, $number2);
else if( $s_kind == 2)
	ShowSumData3( $maxquestion, $result, $grades, $katataksi, $number1, $number2);

function ShowSumData1( $maxquestion, $grades, $katataksi, $countsx)
{
    global $CFG, $gameid, $s_countquestions, $s_sort;
    
    $lines = array();
    
   $query = "SELECT userid,question,SUM(grade) as sumgrade ".
         " FROM {$CFG->prefix}hits h WHERE todelete=0".
         " GROUP BY userid,question ORDER BY userid,question DESC";
    $result = mysql_query( $query);
    
    $countq = ($s_countquestions ? $s_countquestions : $maxquestion);
            
    $a = array();
    $userid = 0;
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
        if( $userid != $rec[ 'userid'])
        {
            if( $userid != 0)
                FlushArray( $a, $lines, $maxquestion, $countq, $userid, $katataksi);
            $userid = $rec[ 'userid'];
        }
        $question = $rec[ 'question'];
        $a[ $question] = $rec[ 'sumgrade'];
    }
    FlushArray( $a, $lines, $maxquestion, $countq, $userid, $katataksi);

    $query = "SELECT id,name,sortorder FROM {$CFG->prefix}users WHERE userlevel=0 AND gameid=$gameid ORDER BY name";
    $result = mysql_query( $query);
    echo '<table border=1>';
    //Τυπώνω την επικεφαλίδα    
    echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td colspan=50><b><center>Βαθμολογία</center></b></td></tr>';
    echo '<tr><td><b>Σχολείο</td><td><b>Συνολική Βαθμολογία</td><td><b>Κατάταξη</b></td>';
    for($i=$maxquestion; $i > 0; $i--)
    {
    	echo '<td>'.$i.'</td>';
       	if( ++$j >= $countq)
        	break;
    }
    echo '</tr>';
    $sortlines = array();
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
        $userid = $rec[ 'id'];
        $line = '<tr><td>'.$rec[ 'name'].'</td>';
        if( array_key_exists( $userid, $grades))
            $grade = $grades[ $userid];
        else
            $grade = 0;
		$line .= '<td><center>'.$grade.'</td>';
    
        $userid = $rec[ 'id'];
        if( array_key_exists( $userid, $lines))
            $line .= $lines[ $userid];
        else
        {
            $line .= '<td><center>'.$katataksi[ $userid].'</center></td>';
            for($i=$countq; $i > 0; $i--)
                $line .= '<td><center>0</td>';   
        }
        $line .= '</tr>';

		if( $s_sort == 'grade')
	    	$key = sprintf( '%10d', $katataksi[ $userid]).$rec[ 'name'];
		else
	    	$key = sprintf( '%10d', $rec[ 'sortorder']).$rec[ 'name'];

		$sortlines[ $key] = $line;
    }
    ksort( $sortlines);
	$i = 1;
    foreach( $sortlines as $line)
	{
		echo $line;
		if( $i++ >= $countsx)
			break;
	}
}

function GetLinkAll()
{
	global $s_kind, $s_countquestions, $s_sort, $s_top;

	$ret = '<form id="formparam" action="results.php">';

	$ret .= 'Στήλες: ';
	for($i=1; $i <= 2; $i++)
	{
		if( $i > 1)
			$ret .= ',';

		if( $i == $s_kind)
			$ret .= $i;
		else
			$ret .= " <a href=\"results.php?kind=$i&count=$s_countquestions&sort=$s_sort&top=$s_top\">$i</a>";
	}
	
	$ret .= '<input type="hidden" name="kind" value="'.$s_kind.'">';
	$ret .= '<input type="hidden" name="sort" value="'.$s_sort.'">';
	$ret .= '<input type="hidden" name="top" value="'.$s_top.'">';
	$ret .= ' Ερωτήσεις: ';
	$ret .= '<input type="text" size="1" name="count" value="'.$s_countquestions.'" onkeypress="OnKeyPress(event);"  >';

    $ret .= ' Ταξινόμηση: ';

	$s = 'Βαθμ.';
    if( $s_sort == 'grade')
		$ret .= ' '.$s;
    else
		$ret .= " <a href=\"results.php?kind=$s_kind&count=$s_countquestions&sort=grade&top=$s_top\">".$s."</a>";

	$s = 'Αλφαβ.';
    if( $s_sort != 'grade')
		$ret .= ' '.$s;
    else
		$ret .= " <a href=\"results.php?kind=$s_kind&count=$s_countquestions&top=$s_top\">".$s."</a>";

    $ret .= '</form>';

	$ret .= '<script>';
	$ret .= "function OnKeyPress()	
		{
			var ch = ('charCode' in event) ? event.charCode : event.keyCode;
			if( ch == 13)
				document.getElementById(\"formTop\").submit();
		}";
	$ret .= '</script>';

    return $ret;
}

function GetLinkKind()
{
	global $s_kind, $s_countquestions, $s_sort, $s_top;

	$ret = '';
	for($i=1; $i <= 3; $i++)
	{
		if( $ret != '')
			$ret .= ',';

		if( $i == $s_kind)
			$ret .= $i;
		else
			$ret .= " <a href=\"results.php?kind=$i&count=$s_countquestions&sort=$s_sort&top=$s_top\">$i</a>";
	}

	$ret .= ' Ερωτ.:';
	$ret .= '<form id="formTop" action="results.php">';
	$ret .= '<input type="hidden" name="kind" value="'.$s_kind.'">';
	$ret .= '<input type="hidden" name="sort" value="'.$s_sort.'">';
	$ret .= '<input type="hidden" name="top" value="'.$s_top.'">';
	$ret .= '<input type="text" size="1" name="count" value="'.$s_countquestions.'" onkeypress="OnKeyPress(event);"  >';
    $ret .= '</form>';

	return $ret;
}

function GetLinkSort()
{
    global $s_kind, $s_countquestions, $s_sort, $s_top;

    $ret = 'Ταξιν.: ';

    if( $s_sort == 'grade')
		$ret .= ' Bαθμ.';
    else
		$ret .= " <a href=\"results.php?kind=$s_kind&count=$s_countquestions&sort=grade&top=$s_top\">Bαθμ.</a>";

    if( $s_sort != 'grade')
		$ret .= ' Αλφαβ.';
    else
		$ret .= " <a href=\"results.php?kind=$s_kind&count=$s_countquestions&top=$s_top\">Αλφαβ.</a>";

    return $ret;
}

function GetLinkTop()
{
   global $s_kind, $s_countquestions, $s_sort, $s_top;

	$ret = '<form id="formTop" action="results.php">';
	$ret .= '<input type="hidden" name="kind" value="'.$s_kind.'">';
	$ret .= '<input type="hidden" name="sort" value="'.$s_sort.'">';
	$ret .= '<input type="hidden" name="count" value="'.$s_countquestions.'">';
	$ret .= '<input type="text" size="1" name="top" value="'.$s_top.'" onkeypress="OnKeyPress(event);"  >';
    $ret .= '</form>';

	$ret .= '<script>';
	$ret .= "function OnKeyPress()	
		{
			var ch = ('charCode' in event) ? event.charCode : event.keyCode;
			if( ch == 13)
				document.getElementById(\"formTop\").submit();
		}";
	$ret .= '</script>';

    return $ret;
}

function FlushArray( &$a, &$lines, $maxquestion, $countq, $userid, $katataksi)
{
    $j=0;
    
    if( $katataksi == false)
	    $s = '';
    else
        $s = '<td><center>'.$katataksi[ $userid].'</center></td>';

    for($i=$maxquestion; $i > 0; $i--)
    {
        if( array_key_exists( $i, $a))
            $s .= '<td><center>'.$a[ $i].'</td>';
        else
            $s .= '<td><center>0</center></td>';
            
        if( ++$j >= $countq)
            break;            
    }
    $lines[ $userid] = $s;
    
    $a = array();
}

function ComputeSumGrades( &$katataksi)
{
    global $CFG, $gameid, $s_sort, $s_countquestions, $s_kind;
    
    $query = "SELECT userid,SUM(grade) as sumgrade,COUNT(*) as countgrade ".
         " FROM {$CFG->prefix}hits h ".
         " WHERE todelete=0 AND grade>0 AND gameid=$gameid".
         " GROUP BY userid";
    $result = mysql_query( $query);
    $grades = array();
	$scores = array();
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
        $userid = $rec[ 'userid'];
        $grades[ $userid] = $rec[ 'sumgrade'];
		$score = 10000*$rec[ 'sumgrade'] + $rec[ 'countgrade'];
		$scores[ $userid] = $score;
    }

    $query = "SELECT * FROM {$CFG->prefix}users WHERE userlevel=0 AND gameid=$gameid ORDER BY sortorder";
	$result = mysql_query( $query);
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
	{
        $id = $rec[ 'id'];
		if( !array_key_exists( $id, $scores))
			$scores[ $id] = 0;
	}	
	arsort( $scores);
	
	$doublescore = array();
	foreach( $scores as $userid => $score)
	{
	    if( !array_key_exists( $score, $doublescore))
	        $doublescore[ $score] = 1;
	    else
	        $doublescore[ $score] += 1;
	}

	$katataksi = array();
	$seira = 0;
	$lastscore = -1;
	foreach( $scores as $userid => $score)
	{
        if( $doublescore[ $score] == 1)
    		$katataksi[ $userid] = ++$seira;
    	else
    	{
    	    ++$seira;
    	    if( $score != $lastscore)
    	    {
    	        $lastseira = $seira;
    	        $lastscore = $score;
    	    }
    	    
    	    $katataksi[ $userid] = $lastseira;
    	}
	}

    return $grades;
}

?>               
    <script type="text/JavaScript">
    function timedRefresh(timeoutPeriod) {
    	setTimeout("OnTimer();",timeoutPeriod);
		
		var field = document.forms['formTop'].elements['top'];
		field.focus();
		field.select();
    }
    
    function OnTimer()
    {
        location.href = 'results.php?kind=<?php echo $s_kind;?>&sort=<?php echo $s_sort;?>&count=<?php echo $s_countquestions;?>&top=<?php echo $s_top; ?>';
    
        timedRefresh(5000);
    }
    timedRefresh(5000);
    </script>
 <?php   


function ShowSumData2( $maxquestion, $result, $grades, $katataksi, $count1, $count2)
{
    global $CFG, $gameid, $s_countquestions, $s_sort;

    $lines = array();
    
    $query = "SELECT userid,question,SUM(grade) as sumgrade ".
         " FROM {$CFG->prefix}hits h WHERE todelete=0".
         " AND question > ".($maxquestion-$countq).
         " GROUP BY userid,question ORDER BY userid,question DESC";
    $result = mysql_query( $query);
        
   	$countq = ($s_countquestions ? $s_countquestions : $maxquestion);
    
    $a = array();
    $userid = 0;
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
        if( $userid != $rec[ 'userid'])
        {
            if( $userid != 0)
                FlushArray( $a, $lines, $maxquestion, $countq, $userid, $katataksi);
            $userid = $rec[ 'userid'];
        }
        $question = $rec[ 'question'];        
        $a[ $question] = $rec[ 'sumgrade'];
    }
    FlushArray( $a, $lines, $maxquestion, $countq, $userid, $katataksi);

    $query = "SELECT id,name FROM {$CFG->prefix}users WHERE userlevel=0 AND gameid=$gameid ORDER BY sortorder";
    $result = mysql_query( $query);
 
    $line = 0;
    $sortlines = array();    
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
    	$userid = $rec[ 'id'];
        $s = '<tr><td>'.$rec[ 'name'].'</td>';
        if( array_key_exists( $userid, $grades))
            $s .= '<td><center>'.$grades[ $userid].'</td>';
        else
            $s .= '<td><center>0</td>';
    
        $userid = $rec[ 'id'];
        if( array_key_exists( $userid, $lines))
            $s .= $lines[ $userid];
        else
        {
            $s .= '<td><center>'.$katataksi[ $userid].'</center></td>';
            $j = 0;
            for($i=$maxquestion; $i > 0; $i--)
            {
                $s .= '<td><center>0</td>';  
                if( ++$j > $countq)
                    break;
            }
        }

		if( $s_sort == 'grade')
	    	$key = sprintf( '%10d', $katataksi[ $userid]).$rec[ 'name'];
		else
	    	$key = sprintf( '%10d', $rec[ 'sortorder']).$rec[ 'name'];
		$sortlines[ $key] = $s;
    }

	//Εκτύπωση
	ksort( $sortlines);
	$line = 0;
	echo '<table border=0>';
	foreach( $sortlines as $s)
	{
		$line++;
		if( $line == 1)
			echo '<tr><td>';
		else if( $line == $count1+1)
			echo '</table></td><td>';

        if( ($line == 1) || ($line == $count1+1))
		{
        	echo '<table border=1>';
            //Τυπώνω την επικεφαλίδα
            echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>';
			echo '&nbsp;';
			echo '</td><td colspan=50><b><center>Βαθμολογία</center></b></td></tr>';
      		echo '<tr><td><b>Σχολείο</td><td><b>Σύν.</td><td><b>Κατάτ.</b></td>'; 
            $j=0;
            for($i=$maxquestion; $i > 0; $i--)
            {
            	echo '<td><b><center>'.$i.'</center></b></td>';
                if( ++$j >= $countq)
               		break;
             }
             echo '</tr>';
		}
 
		echo $s;

		if( $line >= $count1+$count2)
			break;
	}
    echo '</td></tr></table>';
}

function ShowSumData3( $maxquestion, $result, $grades, $katataksi, $count1, $count2)
{
    global $CFG, $gameid, $s_countquestions, $s_sort;
 
    $countq = ($s_countquestions ? $s_countquestions : $maxquestion);
       
    $lines = array();
    
    $query = "SELECT userid,question,SUM(grade) as sumgrade ".
         " FROM {$CFG->prefix}hits h WHERE todelete=0 AND gameid=$gameid".
         " AND question > ".($maxquestion-$countq).
         " GROUP BY userid,question ORDER BY userid,question DESC";
    $result = mysql_query( $query);
        
    if( $countq == 0)
        $countq = $maxquestion;
    
    $a = array();
    $userid = 0;
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
        if( $userid != $rec[ 'userid'])
        {
            if( $userid != 0)
                FlushArray( $a, $lines, $maxquestion, $countq, $userid, false);
            $userid = $rec[ 'userid'];
        }
        $question = $rec[ 'question'];        
        $a[ $question] = $rec[ 'sumgrade'];
    }
    FlushArray( $a, $lines, $maxquestion, $countq, $userid, false);

    $query = "SELECT id,name,sortorder FROM {$CFG->prefix}users WHERE gameid=$gameid AND userlevel=0 ORDER BY sortorder";
    $result = mysql_query( $query);
 
    $line = 0;
    $sortlines = array();    
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {     
        $userid = $rec[ 'id'];
        $s = '<tr><td>'.$rec[ 'name'].'</td>';
        
        if( array_key_exists( $userid, $grades))
            $sum = $grades[ $userid];
        else
            $sum = 0;

        if( array_key_exists( $userid, $katataksi))
            $seira = $katataksi[ $userid];
        else
            $seira = '&nbsp;';
            
        if( $seira == 1)
            $seira = '<font color="red"><b>'.$seira.'</b></font>';
        else if( $seira == 2)
            $seira = '<font color="green"><b>'.$seira.'</b></font>';
        else if( $seira == 3)
            $seira = '<font color="blue"><b>'.$seira.'</b></font>';
            
        $s .= "<td><center>$seira</td><td><center>$sum</td>";
    
        $userid = $rec[ 'id'];
        if( array_key_exists( $userid, $lines))
            $s .= $lines[ $userid];
        else
        {
            $j = 0;
            for($i=$maxquestion; $i > 0; $i--)
            {
                $s .= '<td><center>0</td>';  
                if( ++$j >= $countq)
                    break;
            }
        }
  
		if( $s_sort == 'grade')
	    	$key = sprintf( '%10d', $katataksi[ $userid]).$rec[ 'name'];
		else
	    	$key = sprintf( '%10d', $rec[ 'sortorder']).$rec[ 'name'];

		$sortlines[ $key] = $s;
    }
	ksort( $sortlines);
	$line = 0;
	echo '<table border=0>';
	foreach( $sortlines as $s)
	{
		$line++;
		if( $line == 1)
			echo '<tr><td>';
		else if( $line == $count1+1)
			echo '</table></td><td>';

        if( ($line == 1) || ($line == $count1+1))
		{
        	echo '<table border=1>';
            //Τυπώνω την επικεφαλίδα
            echo '<tr><td>&nbsp;';
			echo '</td><td>';
			echo '&nbsp;';
			echo '</td><td colspan=50><b><center>Βαθμολογία</center></b></td></tr>';
            echo '<tr><td><b>Σχολείο</td><td><b>Κατ.</td><td><b>Συν.</td>'; 
            $j=0;
            for($i=$maxquestion; $i > 0; $i--)
            {
            	echo '<td><b><center>'.$i.'</center></b></td>';
                if( ++$j >= $countq)
               		break;
             }
             echo '</tr>';
		}
 
		echo $s;

		if( $line >= $count1+$count2)
			break;
	}
    echo '</td></tr></table>';
}
