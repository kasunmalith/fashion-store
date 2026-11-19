<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/bootstrap.php';
$email=$argv[1]??'';$password=getenv('EDGEMART_ADMIN_PASSWORD')?:'';$name=$argv[2]??'Store Administrator';if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12){fwrite(STDERR,"Usage: set EDGEMART_ADMIN_PASSWORD to a strong password, then php bin/admin.php email [name]\n");exit(1);}if(one('SELECT id FROM staff_users WHERE email=?',[$email])){fwrite(STDERR,"That administrator already exists. No change made.\n");exit(1);}q('INSERT INTO staff_users(name,email,password,role,permissions) VALUES(?,?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'Super Admin','[]']);echo "Administrator created. Remove the password environment variable.\n";
