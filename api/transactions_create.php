<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/_json.php";

function clean($s){ return trim((string)$s); }

$customer_id   = clean($_POST['customer_id'] ?? '');
$customer_name = clean($_POST['customer_name'] ?? '');
$amount        = clean($_POST['amount'] ?? ''); // dólares en texto
$currency      = strtoupper(clean($_POST['currency'] ?? 'USD'));
$channel       = clean($_POST['channel'] ?? 'card');
$merchant      = clean($_POST['merchant'] ?? '');
$country       = strtoupper(clean($_POST['country'] ?? 'EC'));
$city          = clean($_POST['city'] ?? '');
$device_id     = clean($_POST['device_id'] ?? '');
$ip_addr       = clean($_POST['ip_addr'] ?? '');

if($customer_id==='') json_out(["ok"=>false,"error"=>"customer_id requerido"],400);
if($customer_name==='') json_out(["ok"=>false,"error"=>"customer_name requerido"],400);
if($merchant==='') json_out(["ok"=>false,"error"=>"merchant requerido"],400);
if(!in_array($channel, ['qr','card','transfer','cash'], true)) json_out(["ok"=>false,"error"=>"channel inválido"],400);
if(!preg_match('/^[A-Z]{2}$/', $country)) json_out(["ok"=>false,"error"=>"country inválido (2 letras, ej: EC)"],400);

$amount = str_replace(",", ".", $amount);
if(!is_numeric($amount)) json_out(["ok"=>false,"error"=>"amount inválido"],400);
$amount_cents = (int)round(((float)$amount) * 100);
if($amount_cents <= 0) json_out(["ok"=>false,"error"=>"amount debe ser > 0"],400);

if($currency==='') $currency='USD';
if(strlen($currency) !== 3) json_out(["ok"=>false,"error"=>"currency inválida (3 letras)"],400);

$tx_code = "TX" . strtoupper(bin2hex(random_bytes(3))); // ej TXA1B2C3

$stmt = $mysqli->prepare("
  INSERT INTO transactions(tx_code, customer_id, customer_name, amount_cents, currency, channel, merchant, country, city, device_id, ip_addr)
  VALUES(?,?,?,?,?,?,?,?,?,?,?)
");
$stmt->bind_param(
  "sssisssssss",
  $tx_code,
  $customer_id,
  $customer_name,
  $amount_cents,
  $currency,
  $channel,
  $merchant,
  $country,
  $city,
  $device_id,
  $ip_addr
);

if(!$stmt->execute()){
  json_out(["ok"=>false,"error"=>$stmt->error],500);
}

json_out([
  "ok"=>true,
  "tx_code"=>$tx_code,
  "id"=>$mysqli->insert_id
]);
