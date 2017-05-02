<?php
include 'vendor/autoload.php';
include 'access.php';
include 'preset.php';

//use Pest;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\Service\REST;

try {
    $adapter = new Pest('https://www.strava.com/api/v3');
    $service = new REST($config['ACCESS_TOKEN'], $adapter);
    $client = new Client($service);

    /*$athlete = $client->getAthlete();
    print_r($athlete);*/

   	//$activities = $client->getAthleteActivities();
    //print_r($activities);
	
    $club = $client->getClub($preset['CLUB_ID']); // Velo-Sokol
	echo '<h2>Клуб '.$club['name'].'</h2>';
    //echo '<pre>'.print_r($club, true).'</pre>';
    
	$clubMembers = $client->getClubMembers($preset['CLUB_ID']);
	echo '<h2>Участники</h2>';
    //echo '<pre>'.print_r($clubMembers, true).'</pre>';
	
	echo '<ul>';
	foreach ($clubMembers as $clubMember) {
		echo '<li>'.$clubMember['firstname'].'</li>';

		/*$activities = $client->getAthleteActivities();
		echo '<ul>';
		foreach ($activities as $activity) {
			echo '<li>'.$activity['name'].'</li>';
		}
		echo '</ul>';*/
	}
	echo '</ul>';
    
	$clubActivities = $client->getClubActivities($preset['CLUB_ID'], NULL, 200);
	echo '<h2>Последние тренировки клуба ('.count($clubActivities).')</h2>';
    echo '<pre>'.print_r($clubActivities, true).'</pre>';
	
	
} catch(Exception $e) {
    print $e->getMessage();
}