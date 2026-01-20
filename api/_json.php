<?php
header('Content-Type: application/json; charset=utf-8');

function json_out($data, $code=200){
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
