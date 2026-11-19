<?php
final class FakeGateway implements PaymentGateway {
 public function __construct(){if(PHP_SAPI!=='cli'||getenv('EDGEMART_TEST')!=='1')throw new RuntimeException('Test adapter is CLI-only.');}
 public function initiate(array $order):array{return ['test_only'=>true,'order'=>$order['number']];}
 public function verify(string $transaction):array{return ['test_only'=>true,'transaction'=>$transaction];}
 public function callback(string $body,array $headers):array{throw new RuntimeException('No live callbacks are supported.');}
 public function refund(string $transaction,int $amount,string $key):array{return ['test_only'=>true,'amount'=>$amount];}
}
