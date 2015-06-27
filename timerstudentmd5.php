<?php

require( 'config.php');
require( 'lib.php');

connectdb();
require_login( 'index.php');

ComputeTimerStudent( $resttime, $question, $questiontext, $md5, $infoanswer);

echo $resttime.'#';
echo "Ερώτηση: $question &nbsp;&nbsp;".$infoanswer;
echo '#'.$md5;
