<?php
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'].'/');
define('ENVIRONMENT', isset($_SERVER['SS_ENV']) ? $_SERVER['SS_ENV'] : 'development');
require BASE_PATH.'vendor/autoload.php';
use Medoo\Medoo;

$pdo = new \Medoo\Medoo([
    'database_type' => 'sqlite',
    'database_file' => BASE_PATH.'database.db'
]);

$query = $pdo->select('computers', '*');
var_dump($query);