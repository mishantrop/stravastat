<?php
include 'vendor/autoload.php';
include 'access.php';
include 'preset.php';
include 'models/activity.php';
include 'models/area.php';
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

	$output = '';
	
    $club = $client->getClub($preset['CLUB_ID']);
	$clubMembers = $client->getClubMembers($preset['CLUB_ID'], 1, 200);
	$clubActivities = $client->getClubActivities($preset['CLUB_ID'], NULL, 200);
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
	
	$pedestalOutput = $stravastat->parser->render('pedestal/pedestalWrapper.tpl', ['output' => $pedestalOutput]);
	
	$output .= $pedestalOutput;
	
	// Участники
	$output .= '<h2>Участники ('.count($clubMembers).')</h2>';
	$output .= '<ul style="display: block;">';
	foreach ($clubMembers as $clubMember) {
		$output .= $stravastat->parser->render('athletes/athleteItem.tpl', [
			'athlete' => $clubMember,
		]);
	}
	$output .= '</ul>';
    
	// Последние тренировки клуба
	$output .= '<h2>Последние тренировки клуба ('.count($clubActivities).')</h2>';
	$output .= '<table class="report-table" id="table-last-activities">';
	$output .= '<thead>';
	$output .= '<tr>
		<th>Дата</th>
		<th>Название</th>
		<th>Дистанция</th>
		<th>Макс. скорость</th>
		<th>Ср скорость</th>
		<th>Чистое время</th>
	</tr>
	</thead>';
	foreach ($clubActivities as $clubActivity) {
		$output .= '<tr>
			<td data-value="'.strtotime($clubActivity['start_date']).'"><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.date('d.m.Y H:i:s', strtotime($clubActivity['start_date'])).'</a></td>
			<td><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.$clubActivity['name'].'</a></td>
			<td>'.$stravastat->convertDistance($clubActivity['distance']).'</td>
			<td>'.$stravastat->convertSpeed($clubActivity['max_speed']).'</td>
			<td>'.$stravastat->convertSpeed($clubActivity['average_speed']).'</td>
			<td data-value="'.strtotime($clubActivity['moving_time']).'">'.$stravastat->convertTime($clubActivity['moving_time']).'</td>
		</tr>';
	}
	$output .= '</table>';
    
	// Исходные данные
	$output .= '<h2>Исходные данные</h2>';
	$output .= '<pre>'.print_r($club, true).'</pre>';
	$output .= '<pre>'.print_r($clubMembers, true).'</pre>';
	$output .= '<pre>'.print_r($clubActivities, true).'</pre>';
	
	// Main layout
	echo $stravastat->parser->render('layout.tpl', ['output' => $output]);
} catch(Exception $e) {
    print $e->getMessage();
}