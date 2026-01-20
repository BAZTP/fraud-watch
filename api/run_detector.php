<?php
require_once __DIR__."/_json.php";

/**
 * DEMO: ejecuta Python desde PHP (XAMPP).
 * Requisitos:
 * - Python instalado y en PATH (o define ruta absoluta)
 * - mysql-connector-python instalado
 * - permisos para ejecutar shell_exec
 */

// Ajusta esto si tu python no está en PATH (Windows ejemplo):
// $PY = "C:\\Python311\\python.exe";
$PY = "python";

// Ruta al script
$script = realpath(__DIR__ . "/../python/detect_fraud.py");
if(!$script) json_out(["ok"=>false,"error"=>"No se encontró detect_fraud.py"],500);

$cwd = realpath(__DIR__ . "/../python");
if(!$cwd) json_out(["ok"=>false,"error"=>"No se encontró carpeta python/"],500);

// Comando seguro
$cmd = escapeshellcmd($PY) . " " . escapeshellarg($script) . " 2>&1";

$old = getcwd();
chdir($cwd);

// Ejecutar
$out = shell_exec($cmd);
chdir($old);

// Validación básica
if($out === null){
  json_out([
    "ok"=>false,
    "error"=>"No se pudo ejecutar Python. Revisa si shell_exec está habilitado y si Python está instalado.",
  ], 500);
}

json_out(["ok"=>true, "output"=>$out]);
