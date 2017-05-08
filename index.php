<?php
$time_start = microtime(true);
define('ENVIRONMENT', isset($_SERVER['SS_ENV']) ? $_SERVER['SS_ENV'] : 'development');
switch (ENVIRONMENT)
{
	case 'development':
		error_reporting(-1);
		ini_set('display_errors', 1);
	break;
	case 'production':

	break;
}

include 'vendor/autoload.php';

$output = '';

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
if (ENVIRONMENT == 'development') {
	echo '<p>Total Execution Time: '.$execution_time.' s</p>';
}

echo $output;