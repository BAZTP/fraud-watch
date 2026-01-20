<?php
require_once __DIR__."/../config/db.php";
header('Content-Type: application/json; charset=utf-8');

$kpis = [];
$kpis['tx_total'] = (int)$mysqli->query("SELECT COUNT(*) FROM transactions")->fetch_row()[0];
$kpis['alerts_total'] = (int)$mysqli->query("SELECT COUNT(*) FROM fraud_alerts")->fetch_row()[0];
$kpis['high_alerts'] = (int)$mysqli->query("SELECT COUNT(*) FROM fraud_alerts WHERE severity='high'")->fetch_row()[0];
$kpis['avg_score'] = (int)$mysqli->query("SELECT COALESCE(AVG(risk_score),0) FROM fraud_alerts")->fetch_row()[0];

$chart = [];
$res = $mysqli->query("
  SELECT DATE(created_at) d, COUNT(*) c
  FROM fraud_alerts
  WHERE created_at >= (NOW() - INTERVAL 14 DAY)
  GROUP BY DATE(created_at)
  ORDER BY d ASC
");
while($r=$res->fetch_assoc()) $chart[]=$r;

echo json_encode(["ok"=>true,"kpis"=>$kpis,"chart"=>$chart], JSON_UNESCAPED_UNICODE);
