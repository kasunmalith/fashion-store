<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
require ROOT.'/vendor/autoload.php';
$configFile=getenv('EDGEMART_CONFIG') ?: ROOT.'/config/local.php';
if (!is_file($configFile)) { http_response_code(503); exit('Run the CLI setup first. See README.md.'); }
$GLOBALS['config']=require $configFile;
ini_set('display_errors','0'); ini_set('log_errors','1'); ini_set('error_log',ROOT.'/storage/logs/php.log');
if(PHP_SAPI!=='cli') { session_name('fashion_store'); session_set_cookie_params(['httponly'=>true,'secure'=>config('secure_cookies',true),'samesite'=>'Lax','path'=>parse_url(config('base_url',''),PHP_URL_PATH) ?: '/']); session_start(); header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: same-origin'); header('X-Frame-Options: DENY'); header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"); }
function config(string $key,mixed $default=null): mixed{return $GLOBALS['config'][$key]??$default;}
function db():PDO {static $db; return $db??=new PDO(config('dsn'),config('user'),config('password'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
function q(string $sql,array $args=[]):PDOStatement{if(preg_match('/^(UPDATE|INSERT|DELETE)\b.*\bsettings\b/i',$sql))unset($GLOBALS['settings_cache']);$s=db()->prepare($sql);$s->execute($args);return $s;}
function one(string $sql,array $args=[]):?array{return q($sql,$args)->fetch()?:null;}
function all(string $sql,array $args=[]):array{return q($sql,$args)->fetchAll();}
function tx(callable $f):mixed{if(db()->inTransaction())return $f();for($attempt=0;;$attempt++){db()->beginTransaction();try{$r=$f();db()->commit();return $r;}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();if($e instanceof PDOException&&in_array((int)($e->errorInfo[1]??0),[1213,1205],true)&&$attempt<2){usleep(random_int(10000,50000));continue;}throw $e;}}}
function fail(string $message,int $status=422,array $fields=[]):never{throw new AppError($message,$status,$fields);}
class AppError extends RuntimeException{public function __construct(string $message,public int $status=422,public array $fields=[]){parent::__construct($message);}}
function e(mixed $x):string{return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function setting(string $key,mixed $default=''):mixed{if(!isset($GLOBALS['settings_cache'])){$GLOBALS['settings_cache']=[];foreach(all('SELECT name,value FROM settings') as $r)$GLOBALS['settings_cache'][$r['name']]=json_decode($r['value'],true);}return $GLOBALS['settings_cache'][$key]??$default;}
function enabled(string $module):bool{return (bool)setting('module_'.$module,false);}
function module(string $module):void{if(!enabled($module))fail('This module is disabled.',403);}
function base(string $path=''):string{return rtrim(config('base_url',''),'/').'/'.ltrim($path,'/');}
function asset(string $path):string{return base($path).'?v='.(is_file(ROOT.'/public/'.$path)?filemtime(ROOT.'/public/'.$path):'1');}
require_once ROOT.'/app/routes.php';
function url(string $page,array $args=[]):string{return prettyUrl($page,$args);}
function csrf():string{return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function checkCsrf():void{if(!hash_equals(csrf(),$_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['_csrf']??''))fail('Your session expired. Refresh this page.',419);}
function money(int|string $minor,?string $currency=null):string{return ($currency??setting('currency_symbol','Rs.')).' '.number_format((int)$minor/100,2);}
function cents(mixed $value):int{$s=trim((string)$value);if(!preg_match('/^\d{1,10}(\.\d{1,2})?$/D',$s))fail('Enter a non-negative amount with at most two decimals.');[$a,$b]=array_pad(explode('.',$s),2,'');return (int)$a*100+(int)str_pad($b,2,'0');}
function integer(mixed $value,int $min=0,int $max=1000000):int{if(filter_var($value,FILTER_VALIDATE_INT)===false||(int)$value<$min||(int)$value>$max)fail('Invalid whole-number quantity.');return (int)$value;}
function audit(string $action,string $entity,int $id,array $data=[]):void{q('INSERT INTO audit_logs(staff_id,action,entity,entity_id,data) VALUES(?,?,?,?,?)',[$_SESSION['staff']['id']??null,$action,$entity,$id,json_encode($data)]);}
function staff(string $permission='view'):array{$s=$_SESSION['staff']??null;if(!$s)fail('Staff sign-in required.',401);$u=one('SELECT * FROM staff_users WHERE id=? AND enabled=1',[$s['id']]);if(!$u)fail('Staff account unavailable.',401);$p=json_decode($u['permissions'],true);if($u['role']!=='Super Admin'&&!in_array($permission,$p,true))fail('You do not have permission for this action.',403);return $u;}
function allowed(string $permission):bool{try{staff($permission);return true;}catch(AppError){return false;}}
function customer():int{module('accounts');return (int)($_SESSION['customer_id']??0);}
function safeLink(string $link):string{if(str_starts_with($link,'index.php?')){parse_str(substr($link,10),$parts);$page=(string)($parts['page']??'home');unset($parts['page']);return url($page,$parts);}if(preg_match('~^(shop|category/[a-z0-9-]+|brand/[a-z0-9-]+|product/[a-z0-9-]+|policy/[a-z0-9-]+|cart|checkout|contact|track)/?(\?[a-zA-Z0-9_=&%.-]+)?$~D',$link))return base($link);if($link===''||preg_match('~^(https?://|/|\?)~i',$link))return $link;return '#';}
function pageInput():array{$raw=file_get_contents('php://input');$data=json_decode($raw,true);return is_array($data)?$data:$_POST;}
function respond(mixed $data,int $status=200):never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'data'=>$data],JSON_THROW_ON_ERROR);exit;}
function errorResponse(Throwable $ex):never{if($ex instanceof PDOException&&$ex->getCode()==='23000')$ex=new AppError('A unique value is already in use, or this record has dependent records. Check the SKU, slug, email, and referenced records.');if(!($ex instanceof AppError))error_log((string)$ex);http_response_code($ex instanceof AppError?$ex->status:500);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'message'=>$ex instanceof AppError?$ex->getMessage():'An internal error occurred. Please try again.','fields'=>$ex instanceof AppError?$ex->fields:[]]);exit;}
try{$tz=setting('timezone','Asia/Colombo');date_default_timezone_set($tz);q('SET time_zone = ?',[(new DateTimeImmutable('now',new DateTimeZone($tz)))->format('P')]);}catch(PDOException $ex){if((int)($ex->errorInfo[1]??0)!==1146)throw $ex;}
