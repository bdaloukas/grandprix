<?php

require( 'config.php');
require( 'lib.php');

connectdb();
require_login( 'index.php');

$result = mysql_query( "SELECT * FROM {$CFG->prefix}game WHERE id=$gameid");
$game = mysql_fetch_assoc( $result);
$question = $game[ 'question'];

$resttime = $game[ 'timefinish'] - time();

if( $resttime >= 0)
    echo 'Υπόλοιπο χρόνου: '.$resttime.'<br>';

$query = "SELECT COUNT(DISTINCT userid) as c FROM {$CFG->prefix}hits WHERE question=$question AND gameid=$gameid";
$result = mysql_query( $query);
$rec = mysql_fetch_assoc( $result);
echo 'Απάντησαν: '.$rec[ 'c'].' σχολεία';

if( $resttime <= 0)
    ShowSxoleiaNoAnswer( $question);


function ShowSxoleiaNoAnswer( $question)
{
    global $CFG, $gameid;

    $query = "SELECT u.id,u.name FROM {$CFG->prefix}users u WHERE ".
            " userlevel=0 AND gameid=$gameid AND ".
            " NOT EXISTS(SELECT * FROM {$CFG->prefix}hits h WHERE u.id=h.userid AND h.question=$question)";
    $result = mysql_query( $query);
    $s = '';
    while ( ($rec = mysql_fetch_assoc( $result)) != false)
    {
        $s .= ', '.$rec['name'];
    }
    if( $s != '')
        echo '<br><b>Δεν απάντησαν τα: '.substr( $s, 2);
}
