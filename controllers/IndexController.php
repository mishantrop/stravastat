<?php
use StravaStat\ReportGenerator;

class IndexController {
	public function __construct()
	{
		
	}
	
	public function index()
	{
		$time_start = microtime(true);

		include 'models/ReportGenerator.php';
	
		$output = '';
	
		$loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].'/assets/templates');
		$parser = new Twig_Environment($loader, [
			//'cache' => $_SERVER['DOCUMENT_ROOT'].'/assets/templates/cache',
			'cache' => false,
		]);
		$reportGenerator = new ReportGenerator();
		$period = $reportGenerator->getLastWeekRange();
	
		$time_end = microtime(true);
		$execution_time = ($time_end - $time_start);
	
		$output = $parser->render('layoutIndex.tpl', [
			'output' => $output,
			'start' => date('d.m.Y', $period[0]),
			'end' => date('d.m.Y', $period[1]),
			't' => $execution_time,
			'assets_version' => time(),
		]);
	
		echo $output;
	}

}