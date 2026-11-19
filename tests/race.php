<?php
require dirname(__DIR__).'/app/bootstrap.php';$_SESSION=[];
try{Commerce::checkout([(int)$argv[1]=>1],['name'=>'Race QA','email'=>'race@example.test','phone'=>'000','billing_address'=>'Test','shipping_address'=>'Test','delivery'=>'collection','payment_method'=>'cash','policy'=>1,'idempotency_key'=>bin2hex(random_bytes(16))]);echo 'success';}catch(Throwable){echo 'rejected';}
