<?php
$time_start = round(microtime(true), 4);
set_time_limit(360);
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'].'/');
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

if (!file_exists(BASE_PATH.'vendor/autoload.php')) {
	die('Install Composer and packages from composer.json');
} else {
	include BASE_PATH.'vendor/autoload.php';
}
if (!file_exists(BASE_PATH.'access.php')) {
	die('Copy access.example.php to access.php and get fill one');
} else {
	include BASE_PATH.'access.php';
}
if (!file_exists(BASE_PATH.'preset.php')) {
	$preset = [
		'CLUB_ID' => NULL,
	];
} else {
	include BASE_PATH.'preset.php';
}
if (!isset($preset['CLUB_ID'])) {
	$preset['CLUB_ID'] = NULL;
}

$autoload = [
	'model' => [
		'activity',
		'area',
		'ReportGenerator',
		'stravastat',
	],
];
foreach ($autoload['model'] as $model) {
	if (!file_exists(BASE_PATH.'models/'.$model.'.php')) {
		die('Model '.$model.'does not exists');
	} else {
		include 'models/'.$model.'.php';
	}
}

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

	$loader = new Twig_Loader_Filesystem(BASE_PATH.'assets/templates');
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

	$period = $stravastat->reportGenerator->getLastWeekRange();
	if (isset($_POST['start']) && isset($_POST['end'])) {
		$period = [
			strtotime($_POST['start']),
			strtotime($_POST['end']) + 86400 - 1
		];
	}
	
	if (isset($_POST['club'])) {
		$preset['CLUB_ID'] = (int)$_POST['club'];
	}
	$useCache = (isset($_POST['usecache']) && $_POST['usecache'] == 1);

	$output = '';

	$time_start_data = round(microtime(true), 4); // Time to get data
	if ($useCache && file_exists('cache/club.json')) {
    	$club = json_decode(file_get_contents('cache/club.json'), true);
		if (!is_array($club)) {
			die('Club cache is empty');
		}
	} else {
		$club = $client->getClub($preset['CLUB_ID']);
		file_put_contents('cache/club.json', json_encode($club, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}
	
	if ($useCache && file_exists('cache/athletes.json')) {
		$clubMembers = json_decode(file_get_contents('cache/athletes.json'), true);
		if (!is_array($clubMembers)) {
			die('Athletes cache is empty');
		}
	} else {
		$clubMembers = $client->getClubMembers($preset['CLUB_ID'], 1, 200);
		file_put_contents('cache/athletes.json', json_encode($clubMembers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}
	
	// Ids of athletes
	$athletesBlacklist = [];

	foreach ($clubMembers as $clubMemberIdx => $clubMember) {
		/**
		 * Если у пользователя нет аватарки, ставим ему статическую заглушку.
		 * Если есть аватарка, то сохраняем её в кэш, чтобы каждый раз не обращаться к серверу стравы.
		 */
		if (substr_count($clubMembers[$clubMemberIdx]['profile'], 'http') <= 0) {
			$clubMembers[$clubMemberIdx]['profile'] = 'assets/images/photo.jpg';
		} else {
			if (!file_exists(BASE_PATH.'cache/avatars/'.$clubMember['id'].'.jpg')) {
				$avatarContent = file_get_contents($clubMembers[$clubMemberIdx]['profile']);
				file_put_contents(BASE_PATH.'cache/avatars/'.$clubMember['id'].'.jpg', $avatarContent);
			}
			$clubMembers[$clubMemberIdx]['profile'] = 'cache/avatars/'.$clubMember['id'].'.jpg';
		}
	}

	$clubActivities = [];
	if ($useCache && file_exists('cache/activities.json')) {
		$clubActivities = json_decode(file_get_contents('cache/activities.json'), true);
		if (!is_array($clubActivities)) {
			die('Activities cache is empty');
		}
	} else {
		for ($i = 1; $i <= 10; $i++) {
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
		file_put_contents('cache/activities.json', json_encode($clubActivities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}

	
	$time_end_data = round(microtime(true), 4);
	$time_start_calc = round(microtime(true), 4); // Time to calc results

	$ignoredActivitiesByTime = [];
	$ignoredActivitiesByWorkout = [];
	$ignoredActivitiesByArea = [];
	$ignoredActivitiesByFlagged = [];
	foreach ($clubActivities as $idx => $clubActivity) {
		// Filter by type (bicycles only!)
		if ($clubActivity['workout_type'] != 10) {
			$ignoredActivitiesByWorkout[] = &$clubActivities[$idx];
			unset($clubActivities[$idx]);
			continue;
		}
		if ($clubActivity['flagged'] == 1) {
			$ignoredActivitiesByFlagged[] = &$clubActivities[$idx];
			unset($clubActivities[$idx]);
			continue;
		}
		// Filter by period
		if (!$stravastat->reportGenerator->inRange(strtotime($clubActivity['start_date']), $period)) {
			//$output .= '<p><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.$clubActivity['name'].' ('.date('H:i d.m.Y', strtotime($clubActivity['start_date'])).')</a> does not match period</p>';
			$ignoredActivitiesByTime[] = &$clubActivities[$idx];
			unset($clubActivities[$idx]);
			continue;
		}
		// Filter by area
		if (!$stravastat->matchToArea($clubActivity)) {
			//$output .= '<p>Activity <a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.$clubActivity['name'].'</a> does not match</p>';
			$ignoredActivitiesByArea[] = &$clubActivities[$idx];
			unset($clubActivities[$idx]);
			continue;
		}
	}

	$output .= $stravastat->parser->render('clubs/club-bage.tpl', ['club' => $club]);

	// Рекорд по общей дистанции
	$athletesDistances = [];
	foreach ($clubActivities as $clubActivity) {
		$clubActivity = (array)$clubActivity;
		if (!isset($athletesDistances[$clubActivity['athlete']['id']])) {
			$athletesDistances[$clubActivity['athlete']['id']] = 0;
		}
		$athletesDistances[$clubActivity['athlete']['id']] += round((float)$clubActivity['distance'], 2);
	}
	$maxTotalDistance = 0;
	$totalDistanceAthleteId = null;
	$totalDistanceAthlete = null;
	foreach ($athletesDistances as $athleteId => $distanceSum) {
		if ((float)$distanceSum > (float)$maxTotalDistance) {
			$maxTotalDistance = round((float)$distanceSum, 2);
			$totalDistanceAthleteId = (int)$athleteId;
		}
	}
	if ($totalDistanceAthleteId > 0) {
		foreach ($clubMembers as $clubMember) {
			if ($clubMember['id'] == $totalDistanceAthleteId) {
				$totalDistanceAthlete = $clubMember;
				break;
			}
		}
	}

	$pedestalOutput = '';
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'title' => 'Общая дистанция',
		'label' => 'Общая дистанция',
		'value' => $stravastat->convertDistance($maxTotalDistance),
		'units' => 'км',
		'athlete' => $totalDistanceAthlete,
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
	if ($maxDistanceAthlete !== null) {
		if (substr_count($maxDistanceAthlete['profile'], 'http') <= 0) {
			$maxDistanceAthlete['profile'] = 'assets/images/photo.jpg';
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
	if ($maxSpeedAthlete !== null) {
		if (substr_count($maxSpeedAthlete['profile'], 'http') <= 0) {
			$maxSpeedAthlete['profile'] = 'assets/images/photo.jpg';
		}
	}
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'title' => 'Максимальная скорость',
		'label' => 'Максимальная скорость',
		'value' => $stravastat->convertSpeed($maxSpeed),
		'units' => 'км/ч',
		'athlete' => $maxSpeedAthlete,
	]);
	
	// Суммарный подъём [id => climb]
	$athletesToClimb = [];
	foreach ($clubMembers as $clubMember) {
		$athletesToClimb[$clubMember['id']] = 0.0;
		foreach ($clubActivities as $clubActivity) {
			if ($clubActivity['athlete']['id'] == $clubMember['id']) {
				$athletesToClimb[$clubMember['id']] += round((float)$clubActivity['total_elevation_gain'], 2);
			}
		}
	}
	$maxClimbSum = 0;
	$maxClimbSumAthlete = null;
	$maxClimbSumAthleteId = null;
	foreach ($athletesToClimb as $athleteId => $climbSum) {
		if ($climbSum > $maxClimbSum) {
			$maxClimbSum = $climbSum;
			$maxClimbSumAthleteId = $athleteId;
		}
	}
	foreach ($clubMembers as $clubMember) {
		if ($clubMember['id'] == $maxClimbSumAthleteId) {
			$maxClimbSumAthlete = &$clubMember;
			break;
		}
	}
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'title' => 'Подъём',
		'label' => 'Подъём',
		'value' => (int)$maxClimbSum,
		'units' => 'м',
		'athlete' => $maxClimbSumAthlete,
	]);
	
	// Pedestal
	$output .= $stravastat->parser->render('pedestal/pedestalWrapper.tpl', [
		'output' => $pedestalOutput,
		'period' => date('d.m.Y', $period[0]).' - '.date('d.m.Y', $period[1])
	]);
	
	$time_end_calc = round(microtime(true), 4);

	// Medals
	$medalsOutput = '';
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $totalDistanceAthlete,
		'discipline' => 'totaldistance',
		'value' => $stravastat->convertDistance($maxTotalDistance),
		'units' => 'км',
	]);
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $maxDistanceAthlete,
		'discipline' => 'maxdistance',
		'value' => $stravastat->convertDistance($maxDistance),
		'units' => 'км',
	]);
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $maxSpeedAthlete,
		'discipline' => 'maxspeed',
		'value' => $stravastat->convertSpeed($maxSpeed),
		'units' => 'км/ч',
	]);
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $maxClimbSumAthlete,
		'discipline' => 'climb',
		'value' => (int)$maxClimbSum,
		'units' => 'м',
	]);
	$output .= $stravastat->parser->render('medals/medalsWrapper.tpl', [
		'output' => $medalsOutput,
	]);

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

	// Output last club activities
	$activitiesOutput = '';
	$activitiesJsOutput = '';
	foreach ($clubActivities as $clubActivity) {
		$activitiesOutput .= $stravastat->parser->render('activities/activitiesItem.tpl', [
			'startDateTimestamp' => strtotime($clubActivity['start_date']),
			'startDateDate' => date('d.m.Y H:i:s', strtotime($clubActivity['start_date'])),
			'movingTimeTimestamp' => strtotime($clubActivity['moving_time']),
			'stravastat' => $stravastat,
			'activity' => $clubActivity,
		]);
		$activitiesJsOutput .= '{"title":"'.htmlentities($clubActivity['name']).'", "lat": "'.$clubActivity['start_latlng'][0].'", "lng": "'.$clubActivity['start_latlng'][1].'"},';
	}
	$output .= $stravastat->parser->render('activities/activitiesWrapper.tpl', [
		'activitiesCount' => count($clubActivities),
		'output' => $activitiesOutput
	]);
	$output .= '<script>window.mapActivities = ['.$activitiesJsOutput.'];</script>';
	$output .= $stravastat->parser->render('activities/activitiesMap.tpl', []);

	// Raw Responses
	if (isset($_POST['debug'])) {
		$output .= '<h2>Исходные данные</h2>';
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Club',
			'content' => '<pre>'.print_r($club, true).'</pre>'
		]);
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Athletes',
			'content' => '<pre>'.print_r($clubMembers, true).'</pre>'
		]);
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Activities',
			'content' => '<pre>'.print_r($clubActivities, true).'</pre>'
		]);
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Activities Ignored by time ('.count($ignoredActivitiesByTime).')',
			'content' => '<pre>'.print_r($ignoredActivitiesByTime, true).'</pre>'
		]);
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Activities Ignored by workout type ('.count($ignoredActivitiesByWorkout).')',
			'content' => '<pre>'.print_r($ignoredActivitiesByWorkout, true).'</pre>'
		]);
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Activities Ignored by area ('.count($ignoredActivitiesByArea).')',
			'content' => '<pre>'.print_r($ignoredActivitiesByArea, true).'</pre>'
		]);
		$output .= $stravastat->parser->render('etc/spoiler.tpl', [
			'title' => 'Activities Ignored by flagged ('.count($ignoredActivitiesByFlagged).')',
			'content' => '<pre>'.print_r($ignoredActivitiesByFlagged, true).'</pre>'
		]);
	}

	$time_end = round(microtime(true), 4);
	$execution_time = ($time_end - $time_start);
	$execution_time_data = ($time_end_data - $time_start_data);
	$execution_time_calc = ($time_end_calc - $time_start_calc);

	// Main layout
	$output = $stravastat->parser->render('layoutMain.tpl', [
		'output' => $output,
		't' => $execution_time,
		'td' => $execution_time_data,
		'tc' => $execution_time_calc,
		'assets_version' => time(),
	]);
	echo $output;

	$output = str_replace('<base href="/" />', '<base href="https://quasi-art.ru/stravastat/" />', $output);
	file_put_contents(BASE_PATH.'reports/report_'.$preset['CLUB_ID'].'_'.date('dmY', $period[0]).'-'.date('dmY', $period[1]).'.html', $output);
} catch(Exception $e) {
    print $e->getMessage();
}
