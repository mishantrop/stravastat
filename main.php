<?php
include 'vendor/autoload.php';
include 'access.php';
include 'preset.php';
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
	$twig = new Twig_Environment($loader, [
	    //'cache' => $_SERVER['DOCUMENT_ROOT'].'/assets/templates/cache',
		'cache' => false,
	]);

	$output = '';

    /*$athlete = $client->getAthlete();
    print_r($athlete);*/

   	//$activities = $client->getAthleteActivities();
    //print_r($activities);
	
    $club = $client->getClub($preset['CLUB_ID']);
	$clubMembers = $client->getClubMembers($preset['CLUB_ID'], 1, 200);
	$clubActivities = $client->getClubActivities($preset['CLUB_ID'], NULL, 200);
	
	// Клуб
	$output .= '<h2>Клуб</h2>';
	$output .= '<a href="https://www.strava.com/clubs/'.$club['id'].'" style="display: block;">
		<img src="'.$club['profile'].'" style="display: block; border-radius: 50%;" />
		<div>'.$club['name'].'</div>
		<div>'.$club['description'].'</div>
		<div>'.$club['country'].', '.$club['state'].', '.$club['city'].'</div>
	</a>';
	
	// Рекорд по суммарной дистанции
	$athletesDistances = [];
	foreach ($clubActivities as $clubActivity) {
		if (!isset($athletesDistances[$clubActivity['athlete']['id']])) {
			$athletesDistances[$clubActivity['athlete']['id']] = 0;
		} else {
			$athletesDistances[$clubActivity['athlete']['id']] += round((float)$clubActivity['distance'], 2);
		}
	}
	$output .= '<pre>'.print_r($athletesDistances, true).'</pre>';
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
			if ($clubMember['id'] == $athleteId) {
				$maxDistanceAthlete = $clubMember;
			}
		}
	}
	$output .= '<h2>Рекорд по общей дистанции</h2>';
	$output .= '<p>Общая дистанция: '.$stravastat->converDistance($maxDistance).' км</p>';
	$output .= '<p>Человек: <a href="https://www.strava.com/athletes/'.$clubMember['id'].'">'.$clubMember['firstname'].' '.$clubMember['lastname'].'</a></p>';
	
	// Рекорд скорости
	$maxSpeed = 0;
	$maxSpeedAthlete = null;
	foreach ($clubActivities as $clubActivity) {
		if ((float)$clubActivity['max_speed'] > $maxSpeed) {
			$maxSpeed = round((float)$clubActivity['max_speed'], 2);
			$maxSpeedAthlete = $clubActivity['athlete'];
		}
	}
	$output .= '<h2>Рекорд скорости</h2>';
	$output .= '<p>Скорость: '.$stravastat->convertSpeed($maxSpeed).' км/ч</p>';
	$output .= '<p>Человек: <a href="https://www.strava.com/athletes/'.$maxSpeedAthlete['id'].'">'.$maxSpeedAthlete['firstname'].' '.$maxSpeedAthlete['lastname'].'</a></p>';
	
	// Участники
	$output .= '<h2>Участники ('.count($clubMembers).')</h2>';
	$output .= '<ul style="display: block;">';
	foreach ($clubMembers as $clubMember) {
		$output .= '<li style="display: block; list-style: none;">
		<img src="'.$clubMember['profile'].'" style="display: block; border-radius: 50%; width: 50px; height: 50px;" />
		<a href="https://www.strava.com/athletes/'.$clubMember['id'].'">'.$clubMember['firstname'].' '.$clubMember['lastname'].'</a>
		</li>';

		/*$activities = $client->getAthleteActivities();
		echo '<ul>';
		foreach ($activities as $activity) {
			echo '<li>'.$activity['name'].'</li>';
		}
		echo '</ul>';*/
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
			<td><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.date('H:i:s: d.m.Y', strtotime($clubActivity['start_date'])).'</a></td>
			<td><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.$clubActivity['name'].'</a></td>
			<td>'.$stravastat->converDistance($clubActivity['distance']).'</td>
			<td>'.$stravastat->convertSpeed($clubActivity['max_speed']).'</td>
			<td>'.$stravastat->convertSpeed($clubActivity['average_speed']).'</td>
			<td>'.$stravastat->convertTime($clubActivity['moving_time']).'</td>
		</tr>';
	}
	$output .= '</table>';
    
	// Исходные данные
	$output .= '<h2>Исходные данные</h2>';
	$output .= '<pre>'.print_r($club, true).'</pre>';
	$output .= '<pre>'.print_r($clubMembers, true).'</pre>';
	$output .= '<pre>'.print_r($clubActivities, true).'</pre>';
	
	
	echo $twig->render('layout.tpl', ['output' => $output]);
	
} catch(Exception $e) {
    print $e->getMessage();
}