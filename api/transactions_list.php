<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/_json.php";

$limit = (int)($_GET['limit'] ?? 50);
if($limit < 1) $limit = 50;
if($limit > 200) $limit = 200;

$q = trim($_GET['q'] ?? '');
$where = " WHERE 1=1 ";
$params = [];
$types = "";

if($q !== ''){
  $where .= " AND (tx_code=? OR customer_id=? OR customer_name LIKE ? OR merchant LIKE ?) ";
  $params[] = $q; $types .= "s";
  $params[] = $q; $types .= "s";
  $like = "%$q%";
  $params[] = $like; $types .= "s";
  $params[] = $like; $types .= "s";
}

$sql = "
  SELECT id, tx_code, customer_id, customer_name, amount_cents, currency, channel,
         merchant, country, city, device_id, ip_addr, created_at
  FROM transactions
  $where
  ORDER BY created_at DESC
  LIMIT $limit
";

$stmt = $mysqli->prepare($sql);
if($types) $stmt->bind_param($types, ...$params);
$stmt->execute();

$res = $stmt->get_result();
$rows=[];
while($r=$res->fetch_assoc()) $rows[]=$r;

json_out(["ok"=>true, "transactions"=>$rows]);
