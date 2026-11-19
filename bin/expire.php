<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/bootstrap.php';
echo Commerce::expire()." unpaid order reservations expired.\n";
