<?php
require_once __DIR__."/../config/db.php";
header('Content-Type: application/json; charset=utf-8');

$min = (int)($_GET['min_score'] ?? 50);
$status = trim($_GET['severity'] ?? 'all');

$where = " WHERE a.risk_score >= ? ";
$params = [$min];
$types = "i";

if(in_array($status, ['low','medium','high'], true)){
  $where .= " AND a.severity=? ";
  $params[] = $status; $types .= "s";
}

$sql = "
  SELECT a.*, t.tx_code, t.customer_name, t.customer_id, t.amount_cents, t.channel, t.merchant, t.country, t.city, t.created_at AS tx_time
  FROM fraud_alerts a
  JOIN transactions t ON t.id=a.transaction_id
  $where
  ORDER BY a.created_at DESC
  LIMIT 200
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows=[];
while($r=$res->fetch_assoc()) $rows[]=$r;

echo json_encode(["ok"=>true,"alerts"=>$rows], JSON_UNESCAPED_UNICODE);
