<?php
include 'vendor/autoload.php';
include 'access.php';
include 'preset.php';

//use Pest;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\Service\REST;

try {
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
	$output .= '<h2>Клуб '.$club['name'].'</h2>';
    //
    
	$clubMembers = $client->getClubMembers($preset['CLUB_ID'], 1, 200);
	$output .= '<h2>Участники ('.count($clubMembers).')</h2>';
	
	$output .= '<ul>';
	foreach ($clubMembers as $clubMember) {
		$output .= '<li>'.$clubMember['firstname'].'</li>';

		/*$activities = $client->getAthleteActivities();
		echo '<ul>';
		foreach ($activities as $activity) {
			echo '<li>'.$activity['name'].'</li>';
		}
		echo '</ul>';*/
	}
	$output .= '</ul>';
    
	$clubActivities = $client->getClubActivities($preset['CLUB_ID'], NULL, 200);
	$output .= '<h2>Последние тренировки клуба ('.count($clubActivities).')</h2>';
	$output .= '<table>';
	$output .= '<tr>
		<td>Название</td>
		<td>Дистанция, м</td>
		<td>Максимальная скорость, м/с</td>
	</tr>';
	foreach ($clubActivities as $clubActivity) {
		$output .= '<tr>
			<td>'.$clubActivity['name'].'</td>
			<td>'.$clubActivity['distance'].'</td>
			<td>'.$clubActivity['max_speed'].' м/с</td>
		</tr>';
	}
	$output .= '</table>';
    
	$output .= '<h2>Исходные данные</h2>';
	$output .= '<pre>'.print_r($club, true).'</pre>';
	$output .= '<pre>'.print_r($clubMembers, true).'</pre>';
	$output .= '<pre>'.print_r($clubActivities, true).'</pre>';
	
	
	echo $twig->render('layout.tpl', ['output' => $output]);
	
} catch(Exception $e) {
    print $e->getMessage();
}