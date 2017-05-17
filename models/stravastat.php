<?php
namespace StravaStat\StravaStat;

class StravaStat {
	public $client = null;
	public $parser = null;
	public $area = null;
	public $reportGenerator = null;
	
	public function __construct()
	{
		if (defined('BASE_PATH')) {
		    $this->basePath = BASE_PATH;
		} else {
			$this->basePath = $_SERVER['DOCUMENT_ROOT'].'/';
		}
	}
	
	public function convertSpeed($speed)
	{
		$speed = round((float)$speed, 2);
		return round($speed / 0.2777777777777778, 2);
	}
	
	public function convertDistance($distance)
	{
		$distance = round((float)$distance, 2);
		return round($distance / 1000, 2);
	}
	
	public function convertTime($time)
	{
		$time = (int)$time;
	    $output = '';
		
	    $days = intval($time / (3600*24));
	    $output .= $days.' ';
		
	    $hours = ($time / 3600) % 24;
		if ($hours < 10) {
			$output .= '0';
		}
	    $output .= $hours.':';
	
	    $minutes = ($time / 60) % 60;
		if ($minutes < 10) {
			$output .= '0';
		}
	    $output .= $minutes.':';
	
	    $seconds = $time % 60;
		if ($seconds < 10) {
			$output .= '0';
		}
	    $output .= $seconds;
	
	    return $output;
	}

	public function convertMemory($size)
	{
	    $unit=array('b','kb','mb','gb','tb','pb');
	    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}

	public function matchToArea($activity)
	{
		$lat = (float)$activity['start_latlng'][0];
		$lng = (float)$activity['start_latlng'][1];
		return $this->area->matchToArea($lat, $lng);
	}

	public function getClub($clubId, $useCache)
	{
		if ($useCache && file_exists($this->basePath.'cache/club.json')) {
			$club = json_decode(file_get_contents($this->basePath.'cache/club.json'), true);
		} else {
			$club = $this->client->getClub($clubId);
			file_put_contents($this->basePath.'cache/club.json', json_encode($club, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		}
		return $club;
	}
	
	public function getClubMembers($clubId, $useCache)
	{
		if ($useCache && file_exists($this->basePath.'cache/athletes.json')) {
			$clubMembers = json_decode(file_get_contents($this->basePath.'cache/athletes.json'), true);
		} else {
			$clubMembers = $this->client->getClubMembers($clubId, 1, 200);
			file_put_contents($this->basePath.'cache/athletes.json', json_encode($clubMembers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		}
		return $clubMembers;
	}

	public function getClubActivities($clubId, $useCache)
	{
		$clubActivities = [];
		if ($useCache && file_exists($this->basePath.'cache/activities.json')) {
			$clubActivities = json_decode(file_get_contents($this->basePath.'cache/activities.json'), true);
			if (!is_array($clubActivities)) {
				die('Activities cache is empty');
			}
		} else {
			for ($i = 1; $i <= 10; $i++) {
				try {
					$activities = $this->client->getClubActivities($clubId, $i, 200);
				} catch (Pest_BadRequest $e) {
					$response = json_decode($e->getMessage());
				}
				if (isset($activities) && is_array($activities)) {
					if (count($activities) == 0) {
						break;
					}
					$clubActivities = array_merge($clubActivities, $activities);
				}
			}
			file_put_contents($this->basePath.'cache/activities.json', json_encode($clubActivities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		}
		return $clubActivities;
	}

	public function filterClubMembersByBlacklist($clubMembers, $blackList)
	{
		foreach ($clubMembers as $clubMemberIdx => $clubMember) {
			if (in_array($clubMember['id'], $blackList)) {
				unset($clubMembers[$clubMemberIdx]);
			}
		}
		return $clubMembers;
	}
	
	public function filterClubActivities($clubActivities, $criteria = [])
	{
		foreach ($clubActivities as $idx => $clubActivity) {
			// Filter by type (bicycles only!)
			if ($clubActivity['workout_type'] != 10) {
				unset($clubActivities[$idx]);
				continue;
			}
			if ($clubActivity['flagged'] == 1) {
				unset($clubActivities[$idx]);
				continue;
			}
			// Filter by period
			if (!$this->reportGenerator->inRange(strtotime($clubActivity['start_date']), $criteria['period'])) {
				unset($clubActivities[$idx]);
				continue;
			}
			// Filter by area
			if (!$this->matchToArea($clubActivity)) {
				unset($clubActivities[$idx]);
				continue;
			}
		}
		return $clubActivities;
	}
	
	public function fillActivitiesAthletes($clubActivities, $clubMembers)
	{
		foreach ($clubActivities as $idx => $clubActivity) {
			foreach ($clubMembers as $clubMember) {
				if ($clubMember['id'] == $clubActivity['athlete']['id']) {
					$clubActivity['athlete'] = $clubMember;
				}
			}
		}
		return $clubActivities;
	}
	
	public function processAvatars($clubMembers)
	{
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
		return $clubMembers;
	}

	public function saveReport($output, $clubId, $period)
	{
		$output = str_replace('<base href="/" />', '<base href="https://quasi-art.ru/stravastat/" />', $output);
		file_put_contents(BASE_PATH.'reports/report_'.$clubId.'_'.date('dmY', $period[0]).'-'.date('dmY', $period[1]).'.html', $output);
	}

}