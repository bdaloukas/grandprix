<?php

require( 'config.php');
require( 'lib.php');

connectdb();
require_login( 'index.php');

ComputeTimerStudent( $resttime, $question, $questiontext, $md5questiontext, $infoanswer);

echo $md5questiontext;
echo '#'.$questiontext;
