<?php
$time_start = round(microtime(true), 4);
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
include 'access.php';
include 'preset.php';
include 'models/activity.php';
include 'models/area.php';
include 'models/ReportGenerator.php';
include 'models/stravastat.php';

//use Pest;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\Service\REST;

try {
	$stravastat = new StravaStat();
	
	// StravaPHP
    $adapter = new Pest('https://www.strava.com/api/v3');
    $service = new REST($config['ACCESS_TOKEN'], $adapter);
    $client = new Client($service);
	
	$loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].'/assets/templates');
	$stravastat->parser = new Twig_Environment($loader, [
	    //'cache' => $_SERVER['DOCUMENT_ROOT'].'/assets/templates/cache',
		'cache' => false,
	]);
	
	// Restrict to Vologda Oblast
	$stravastat->area = new Area();
	$stravastat->area->setStartLat(58.429187);
	$stravastat->area->setStartLng(34.652482);
	$stravastat->area->setEndLat(61.639137);
	$stravastat->area->setEndLng(47.290977);
	
	$stravastat->reportGenerator = new ReportGenerator(time());

	$output = '';
	
    $club = $client->getClub($preset['CLUB_ID']);
	$clubMembers = $client->getClubMembers($preset['CLUB_ID'], 1, 200);
	
	$clubActivities = [];
	for ($i = 1; $i <= 3; $i++) {
		try {
			$activities = $client->getClubActivities($preset['CLUB_ID'], $i, 200);
		} catch (Pest_BadRequest $e) {
			$response = json_decode($e->getMessage());
			$output .= 	$stravastat->parser->render('etc/spoiler.tpl', [
					'title' => 'Exception',
					'content' => (is_object($response)) ?
								'<pre>'.print_r($response, true).'</pre>' :
								'<pre>'.$e->getMessage().'</pre>',
				]);
		}
		if (isset($activities) && is_array($activities)) {
			if (count($activities) == 0) {
				break;
			}
			$clubActivities = array_merge($clubActivities, $activities);
		}
	}
	
	
	$ignoreActivities = [];
	foreach ($clubActivities as $idx => $clubActivity) {
		if ($clubActivity['workout_type'] != 10) {
			unset($clubActivities[$idx]);
		}
		if (!$stravastat->matchToArea($clubActivity)) {
			$output .= '<p>Activity <a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.$clubActivity['name'].'</a> does not match</p>';
			unset($clubActivities[$idx]);
			$ignoreActivities = clone $clubActivities[$idx];
		}
	}
	
	$output .= $stravastat->parser->render('clubs/club-bage.tpl', ['club' => $club]);
	
	// Рекорд по суммарной дистанции
	$athletesDistances = [];
	foreach ($clubActivities as $clubActivity) {
		if (!isset($athletesDistances[$clubActivity['athlete']['id']])) {
			$athletesDistances[$clubActivity['athlete']['id']] = 0;
		}
		$athletesDistances[$clubActivity['athlete']['id']] += round((float)$clubActivity['distance'], 2);
	}
	$maxDistance = 0;
	$maxDistanceAthleteId = null;
	$maxDistanceAthlete = null;
	foreach ($athletesDistances as $athleteId => $distanceSum) {
		if ((float)$distanceSum > (float)$maxDistance) {
			$maxDistance = round((float)$distanceSum, 2);
			$maxDistanceAthleteId = (int)$athleteId;
		}
	}
	if ($athleteId > 0) {
		foreach ($clubMembers as $clubMember) {
			if ($clubMember['id'] == $maxDistanceAthleteId) {
				$maxDistanceAthlete = $clubMember;
				break;
			}
		}
	}

	$pedestalOutput = '';
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'title' => 'Рекорд по общей дистанции',
		'label' => 'Общая дистанция',
		'value' => $stravastat->convertDistance($maxDistance),
		'units' => 'км',
		'athlete' => $maxDistanceAthlete,
	]);
	
	// Рекорд по самому длинному заезду
	$maxDistance = 0;
	$maxDistanceAthlete = null;
	foreach ($clubActivities as $clubActivity) {
		if ((float)$clubActivity['distance'] > $maxDistance) {
			$maxDistance = round((float)$clubActivity['distance'], 2);
			$maxDistanceAthlete = $clubActivity['athlete'];
		}
	}
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'title' => 'Самый длинный заезд',
		'label' => 'Дистанция',
		'value' => $stravastat->convertDistance($maxDistance),
		'units' => 'км',
		'athlete' => $maxDistanceAthlete,
	]);
	
	// Рекорд скорости
	$maxSpeed = 0;
	$maxSpeedAthlete = null;
	foreach ($clubActivities as $clubActivity) {
		if ((float)$clubActivity['max_speed'] > $maxSpeed) {
			$maxSpeed = round((float)$clubActivity['max_speed'], 2);
			$maxSpeedAthlete = $clubActivity['athlete'];
		}
	}
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'title' => 'Рекорд скорости',
		'label' => 'Скорость',
		'value' => $stravastat->convertSpeed($maxSpeed),
		'units' => 'км/ч',
		'athlete' => $maxSpeedAthlete,
	]);
	$output .= $stravastat->parser->render('pedestal/pedestalWrapper.tpl', ['output' => $pedestalOutput]);
	
	// Участники
	$athletesOutput = '';
	foreach ($clubMembers as $clubMember) {
		$athletesOutput .= $stravastat->parser->render('athletes/athleteItem.tpl', [
			'athlete' => $clubMember,
		]);
	}
	$output .= $stravastat->parser->render('athletes/athletesWrapper.tpl', [
		'athletesCount' => count($clubMembers),
		'output' => $athletesOutput
	]);
    
	// Последние тренировки клуба
	$activitiesOutput = '';
	foreach ($clubActivities as $clubActivity) {
		$activitiesOutput .= $stravastat->parser->render('activities/activitiesItem.tpl', [
			'startDateTimestamp' => strtotime($clubActivity['start_date']),
			'startDateDate' => date('d.m.Y H:i:s', strtotime($clubActivity['start_date'])),
			'movingTimeTimestamp' => strtotime($clubActivity['moving_time']),
			'stravastat' => $stravastat,
			'activity' => $clubActivity,
		]);
	}
	$output .= $stravastat->parser->render('activities/activitiesWrapper.tpl', [
		'activitiesCount' => count($clubActivities),
		'output' => $activitiesOutput
	]);
    
	// Исходные данные
	$output .= '<h2>Исходные данные</h2>';
	$output .= $stravastat->parser->render('etc/spoiler.tpl', [
		'title' => 'Club',
		'content' => print_r($club, true)
	]);
	$output .= $stravastat->parser->render('etc/spoiler.tpl', [
		'title' => 'Athletes',
		'content' => print_r($clubMembers, true)
	]);
	$output .= $stravastat->parser->render('etc/spoiler.tpl', [
		'title' => 'Activities',
		'content' => print_r($clubActivities, true)
	]);

	$time_end = round(microtime(true), 4);
	$execution_time = ($time_end - $time_start);

	// Main layout
	echo $stravastat->parser->render('layoutMain.tpl', [
		'output' => $output,
		't' => $execution_time,
		'assets_version' => time(),
	]);
} catch(Exception $e) {
    print $e->getMessage();
}


