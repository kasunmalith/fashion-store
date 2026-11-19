<?php
require dirname(__DIR__).'/app/bootstrap.php';
try{$p=one('SELECT * FROM payments WHERE id=?',[(int)($_GET['id']??0)]);if(!$p||!$p['proof'])fail('Proof not found.',404);Commerce::access((int)$p['order_id'],$_GET['token']??'');if(!preg_match('/^[a-f0-9]{32}\.webp$/D',$p['proof']))fail('Proof not found.',404);header('Content-Type: image/webp');header('Cache-Control: private, no-store');readfile(ROOT.'/storage/private/'.$p['proof']);}catch(Throwable $e){errorResponse($e);}
