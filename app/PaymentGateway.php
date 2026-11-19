<?php
interface PaymentGateway { public function initiate(array $order):array; public function verify(string $transaction):array; public function callback(string $body,array $headers):array; public function refund(string $transaction,int $amount,string $key):array; }
// No live adapter is shipped. A browser return URL can never verify a payment.
