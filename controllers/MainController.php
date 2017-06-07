<?php
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\Service\REST;

use StravaStat\Activity;
use StravaStat\Area;
use StravaStat\Athlete;
use StravaStat\StravaStat;
use StravaStat\ReportGenerator;
use StravaStat\MedalMaxDistance;
use StravaStat\MedalTotalDistance;
use StravaStat\MedalAvgSpeed;
use StravaStat\MedalMaxClimb;

$time_start = round(microtime(true), 4);
set_time_limit(360);

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
		'Activity',
		'Area',
		'Medal',
		'ReportGenerator',
		'StravaStat',
	],
];
foreach ($autoload['model'] as $model) {
	if (!file_exists(BASE_PATH.'models/'.$model.'.php')) {
		die('Model '.$model.'does not exists');
	} else {
		include BASE_PATH.'models/'.$model.'.php';
	}
}

try {
	$stravastat = new StravaStat();
	// StravaPHP
    $stravastat->adapter = new Pest('https://www.strava.com/api/v3');
    $stravastat->service = new REST($config['ACCESS_TOKEN'], $stravastat->adapter);
    $stravastat->client = new Client($stravastat->service);

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

	if (isset($_POST['start']) && isset($_POST['end'])) {
		$period = $stravastat->reportGenerator->createRange($_POST['start'], $_POST['end']);
	} else {
		$period = $stravastat->reportGenerator->getLastWeekRange();
	}
	
	if (isset($_POST['club'])) {
		$preset['CLUB_ID'] = (int)$_POST['club'];
	}
	$useCache = (isset($_POST['usecache']) && $_POST['usecache'] == 1);

	$output = '';
	
	// Club
	$club = $stravastat->getClub($preset['CLUB_ID'], $useCache);
	
	// Athletes
	$athletesBlacklist = []; // Ids
	$clubMembers = $stravastat->getClubMembers($preset['CLUB_ID'], $useCache);
	$clubMembers = $stravastat->filterClubMembersByBlacklist($clubMembers, $athletesBlacklist);
	$clubMembers = $stravastat->processAvatars($clubMembers);

	// Activities
	$clubActivities = $stravastat->getClubActivities($preset['CLUB_ID'], $useCache);
	$clubActivities = $stravastat->filterClubActivities($clubActivities, ['period' => $period]);
	$clubActivities = $stravastat->fillActivitiesAthletes($clubActivities, $clubMembers);

	$output .= $stravastat->parser->render('clubs/club-bage.tpl', ['club' => $club]);

	// Рекорд по общей дистанции
	$medalTotalDistance = new MedalTotalDistance();
	$medalTotalDistance->calc($clubActivities, $clubMembers);
	$medalTotalDistance->value = $stravastat->convertDistance($medalTotalDistance->value);
	$pedestalOutput = '';
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'medal' => $medalTotalDistance,
	]);

	// Рекорд по самому длинному заезду
	$medalMaxDistance = new MedalMaxDistance();
	$medalMaxDistance->calc($clubActivities, $clubMembers);
	$medalMaxDistance->value = $stravastat->convertDistance($medalMaxDistance->value);
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'medal' => $medalMaxDistance,
	]);

	// Рекорд скорости
	/*$medalMaxSpeed = new MedalMaxSpeed();
	$medalMaxSpeed->calc($clubActivities, $clubMembers);
	$medalMaxSpeed->value = $stravastat->convertSpeed($medalMaxSpeed->value);
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'medal' => $medalMaxSpeed,
	]);*/
	
	// Суммарный подъём [id => climb]
	$medalMaxClimb = new MedalMaxClimb();
	$medalMaxClimb->calc($clubActivities, $clubMembers);
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'medal' => $medalMaxClimb,
	]);
	
	// Максимальная средняя скорость
	// (100+10)/(10/40+100/20)=110/5,25
	// 20,95 км/ч
	$medalAvgSpeed = new MedalAvgSpeed();
	$medalAvgSpeed->calc($clubActivities, $clubMembers);
	$medalAvgSpeed->value = $stravastat->convertSpeed($medalAvgSpeed->value);
	$pedestalOutput .= $stravastat->parser->render('pedestal/pedestalItem.tpl', [
		'medal' => $medalAvgSpeed,
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
		'athlete' => $medalTotalDistance->athlete,
		'discipline' => 'totaldistance',
		'value' => $medalTotalDistance->value,
		'units' => $medalTotalDistance->units,
	]);
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $medalMaxDistance->athlete,
		'discipline' => 'maxdistance',
		'value' => $medalMaxDistance->value,
		'units' => $medalMaxDistance->units,
	]);
	/*$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $medalMaxSpeed->athlete,
		'discipline' => 'maxspeed',
		'value' => $medalMaxSpeed->value,
		'units' => $medalMaxSpeed->units,
	]);*/
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $medalMaxClimb->athlete,
		'discipline' => 'climb',
		'value' => $medalMaxClimb->value,
		'units' => $medalMaxClimb->units,
	]);
	$medalsOutput .= $stravastat->parser->render('medals/medalsItem.tpl', [
		'athlete' => $medalAvgSpeed->athlete,
		'discipline' => 'avgspeed',
		'value' => $medalAvgSpeed->value,
		'units' => $medalAvgSpeed->units,
	]);
	$output .= $stravastat->parser->render('medals/medalsWrapper.tpl', [
		'output' => $medalsOutput,
	]);

	// Athletes
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
		foreach ($clubMembers as $clubMember) {
			if ($clubActivity['athlete']['id'] == $clubMember['id']) {
				$clubActivity['athlete'] = $clubMember;
				break;
			}
		}
		
		$activitiesOutput .= $stravastat->parser->render('activities/activitiesItem.tpl', [
			'startDateTimestamp' => strtotime($clubActivity['start_date']),
			'startDateDate' => date('d.m.Y H:i:s', strtotime($clubActivity['start_date'])),
			'movingTimeTimestamp' => strtotime($clubActivity['moving_time']),
			'stravastat' => $stravastat,
			'activity' => $clubActivity,
		]);
		$activitiesJsOutputRaw = new StdClass();
		$activitiesJsOutputRaw->content = $stravastat->parser->render('activities/activitiesMapItem.tpl', [
			'clubActivity' => $clubActivity
		]);
		$activitiesJsOutputRaw->content = str_replace("\n", '', $activitiesJsOutputRaw->content);
		$activitiesJsOutputRaw->lat = $clubActivity['start_latlng'][0];
		$activitiesJsOutputRaw->lng = $clubActivity['start_latlng'][1];
		$activitiesJsOutput .= json_encode($activitiesJsOutputRaw).',';
	}
	$output .= $stravastat->parser->render('activities/activitiesWrapper.tpl', [
		'activitiesCount' => count($clubActivities),
		'output' => $activitiesOutput
	]);
	$output .= '<script>window.mapActivities = ['.$activitiesJsOutput.'];</script>';
	$output .= $stravastat->parser->render('activities/activitiesMap.tpl', []);

	$time_end = round(microtime(true), 4);
	$execution_time = ($time_end - $time_start);

	// Main layout
	$output = $stravastat->parser->render('layoutMain.tpl', [
		'output' => $output,
		'm' => $stravastat->convertMemory(memory_get_usage()),
		't' => $execution_time,
		'assets_version' => time(),
	]);
	echo $output;

	$stravastat->saveReport($output, $preset['CLUB_ID'], $period);
} catch(Exception $e) {
    print $e->getMessage();
}
