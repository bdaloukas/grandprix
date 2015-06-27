<?php

require( 'config.php');
require( 'lib.php');

connectdb();
    
	if( array_key_exists( 'username', $_POST))
    {
        OnLogin2( 'index.php');
        ShowForm();
        die;
    }

    require_login( 'index.php');
    
    echo GetHeader( 'Εμφάνιση ερωτήσεων').'<body>';

    if( array_key_exists( 'answer', $_GET))
        OnGrade( $_GET[ 'answer'], $_SESSION[ 'userid']);
    
    ShowForm();

function ShowForm()
{
	ComputeTimerStudent( $resttime, $question, $questiontext, $md5, $infoanswer);

	if( $resttime <= 0)
	{
		$divanswervisibility = ' style="visibility: hidden"';
	}else
	{
		$divanswervisibility = '';
	}
	$divtimerhtml = "Υπόλοιπο χρόνου: $resttime δευτερόλεπτα";
	$divquestionhtml = $questiontext;
?>
	<div id="divmd5" style="visibility: hidden">
		<?php echo $md5;?>
	</div>

	<div id="divtimer"> 
		<?php echo $divtimerhtml; ?>
	</div>
<div width="100%" id="divquestion">
	<?php echo "Ερώτηση: $question &nbsp;&nbsp;$infoanswer<br>"; ?>
</div>
<div id="divanswer" <?php echo $divanswervisibility;?> >
	<form name="formanswer" id="formanswer" method="get" action="index.php">
        Δώστε 1,2,3,4: <input type="text" id="answer" name="answer" onkeypress="OnKeyPress( event);">
	</form>
</div>

	<div width="100%" height="100%" id="divquestiontext"> 
		<?php echo $divquestionhtml; ?>
	</div>
<br>


	<script type="text/JavaScript">

		document.forms['formanswer'].elements['answer'].focus();
		
		timedRefresh( 1000);

    	function timedRefresh(timeoutPeriod) 
		{
        	setTimeout("OnTimer();",timeoutPeriod);
    	}

    	function OnTimer()
    	{
        	var oReq = new XMLHttpRequest();
        	oReq.onload = reqListenerMD5;
        	oReq.open("get", "timerstudentmd5.php", true);
        	oReq.send();

        	timedRefresh( 1000);
		}

		function OnKeyPress( event)
		{
			var ch = ('charCode' in event) ? event.charCode : event.keyCode;
			ch = ch - 48;

			if( ch >= 1)
 			{
				if( ch <= 4)
				{
					window.location.assign( 'index.php?answer=' + ch);
					return false;
				}
			}		
			
			 var f = document.getElementById( "answer");
			 f.value = "";

			 return true;
		}

    function reqListenerMD5() 
    {
	    var ret = this.responseText;

		var pos=ret.indexOf( "#");
		if( pos != 0)
		{
		 	var timerest = ret.substr( 0, pos);
			ret = ret.substr( pos+1);

			pos = ret.indexOf( "#");
                        
			var question = ret.substr( 0, pos);
			var md5 = ret.substr( pos+1);

			var f = document.getElementById( "divtimer");
			f.innerHTML = "Υπόλοιπο χρόνου: " + timerest + " δευτερόλεπτα";

			var f2 = document.getElementById( "divquestion");
			if( f2.innerHTML != question)
				f2.innerHTML = question;

			f = document.getElementById( "divanswer");
			if( timerest == 0)
				f.style.visibility = 'hidden';
			else
			{
				f.style.visibility = 'visible';
				f = document.forms['formanswer'].elements['answer'];
				f.focus();
				f.select();
			}

			var f3 = document.getElementById( "divmd5");
			if( f3.innerHTML != md5)
			{
				//Πρέπει να διαβάσω και τα υπόλοιπα
        		var oReq = new XMLHttpRequest();
        		oReq.onload = reqListener;
        		oReq.open("get", "timerstudent.php", true);
        		oReq.send();
			}
  		}	
	}

    function reqListener() 
    {
	    var ret = this.responseText;

		var pos=ret.indexOf( "#");
		if( pos != 0)
		{
		 	var md5 = ret.substr( 0, pos);
			var questiontext = ret.substr( pos+1);

			var f3 = document.getElementById( "divquestiontext");
			f3.innerHTML = questiontext;

			var f4 = document.getElementById( "divmd5");
			f4.innerHTML = md5;
  		}	
	}

	</script>
<?php
}

  function OnGrade( $answer, $userid)
  {
    global $CFG, $gameid;
  
    $result = mysql_query( "SELECT * FROM {$CFG->prefix}game WHERE id=$gameid");
    $game = mysql_fetch_assoc( $result);
    $question = $game[ 'question'];
    
    if( time() < $game[ 'timefinish']+2)
        $timeout = 0;
    else
        $timeout = 1;
    $todelete = $timeout;           
    $ip = GetMyIP();
    $timehit = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO {$CFG->prefix}hits(userid,ip,question,answer,timeout,todelete,gameid,timehit) SELECT $userid,'$ip',$question,$answer,$timeout,$todelete,$gameid,'$timehit'";
    mysql_query($query);

    if( $timeout)
        echo 'Απάντηση εκτός χρόνου';
    for(;;)
    {
        $query = "SELECT min(id) as minid,max(id) as maxid FROM {$CFG->prefix}hits WHERE question=$question AND userid=$userid AND todelete=0";
        $result=mysql_query($query);
        if( ($rec = mysql_fetch_assoc( $result)) != false)
        {
            if( $rec[ 'minid'] == $rec[ 'maxid'])
                break;

            $query = "UPDATE {$CFG->prefix}hits SET todelete=1 WHERE id=".$rec[ 'minid'];
            mysql_query($query);
        }
    }
  }
