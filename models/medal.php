<?php
interface Medal {
	public function calc($clubActivities, $clubMembers);
}

class MedalTotalDistance implements Medal {
	public $athlete;
	public $title;
	public $units;
	public $value;
	
	public function __construct() {
		$this->athlete = null;
		$this->title = 'Общая дистанция';
		$this->units = 'км';
		$this->value = 0;
	}
	
	public function calc($clubActivities, $clubMembers) {
		$athletesDistances = [];
		foreach ($clubActivities as $clubActivity) {
			$clubActivity = (array)$clubActivity;
			if (!isset($athletesDistances[$clubActivity['athlete']['id']])) {
				$athletesDistances[$clubActivity['athlete']['id']] = 0;
			}
			$athletesDistances[$clubActivity['athlete']['id']] += round((float)$clubActivity['distance'], 2);
		}
		$totalDistanceAthleteId = 0;
		foreach ($athletesDistances as $athleteId => $distanceSum) {
			if ((float)$distanceSum > (float)$this->value) {
				$this->value = round((float)$distanceSum, 2);
				$totalDistanceAthleteId = (int)$athleteId;
			}
		}
		if ($totalDistanceAthleteId > 0) {
			foreach ($clubMembers as $clubMember) {
				if ($clubMember['id'] == $totalDistanceAthleteId) {
					$this->athlete = $clubMember;
					break;
				}
			}
		}
	}
}

class MedalMaxDistance implements Medal {
	public $title;
	public $value;
	public $units;
	public $athlete;
	
	public function __construct() {
		$this->title = 'Самый длинный заезд';
		$this->value = 0;
		$this->units = 'км';
		$this->athlete = null;
	}
	
	public function calc($clubActivities, $clubMembers) {
		$this->athlete = null;
		foreach ($clubActivities as $clubActivity) {
			if ((float)$clubActivity['distance'] > $this->value) {
				$this->value = round((float)$clubActivity['distance'], 2);
				$this->athlete = $clubActivity['athlete'];
			}
		}
	}
}

class MedalMaxSpeed implements Medal {
	public $title;
	public $value;
	public $units;
	public $athlete;
	
	public function __construct() {
		$this->title = 'Максимальная скорость';
		$this->value = 0;
		$this->units = 'км/ч';
		$this->athlete = null;
	}
	
	public function calc($clubActivities, $clubMembers) {
		foreach ($clubActivities as $clubActivity) {
			if ((float)$clubActivity['max_speed'] > $this->value) {
				$this->value = round((float)$clubActivity['max_speed'], 2);
				$this->athlete = $clubActivity['athlete'];
			}
		}
	}
}

class MedalMaxClimb implements Medal {
	public $title;
	public $value;
	public $units;
	public $athlete;
	
	public function __construct() {
		$this->title = 'Подъём';
		$this->value = 0;
		$this->units = 'м';
		$this->athlete = null;
	}
	
	public function calc($clubActivities, $clubMembers) {
		$athletesToClimb = [];
		foreach ($clubMembers as $clubMember) {
			$athletesToClimb[$clubMember['id']] = 0.0;
			foreach ($clubActivities as $clubActivity) {
				if ($clubActivity['athlete']['id'] == $clubMember['id']) {
					$athletesToClimb[$clubMember['id']] += round((float)$clubActivity['total_elevation_gain'], 2);
				}
			}
		}
		$maxClimbSumAthleteId = null;
		foreach ($athletesToClimb as $athleteId => $climbSum) {
			if ($climbSum > $this->value) {
				$this->value = $climbSum;
				$maxClimbSumAthleteId = $athleteId;
			}
		}
		foreach ($clubMembers as $clubMember) {
			if ($clubMember['id'] == $maxClimbSumAthleteId) {
				$this->athlete = &$clubMember;
				break;
			}
		}
	}
}

class MedalAvgSpeed implements Medal {
	public $title;
	public $value;
	public $units;
	public $athlete;
	
	public function __construct() {
		$this->title = 'Максимальная средняя скорость';
		$this->value = 0;
		$this->units = 'км/ч';
		$this->athlete = null;
	}
	
	public function calc($clubActivities, $clubMembers) {
		// (100+10)/(10/40+100/20)=110/5,25
		// 20,95 км/ч
		$clubMembersSpeeds = [];
		$clubMembersAvgSpeeds = [];
		foreach ($clubActivities as $clubActivity) { //
			$record = [
				'distance' => round((float)$clubActivity['distance'], 2),
				'avgspeed' => round((float)$clubActivity['average_speed'], 2),
			];
			if (!isset($clubMembersSpeeds[$clubActivity['athlete']['id']])) {
				$clubMembersSpeeds[$clubActivity['athlete']['id']] = [$record];
			} else {
				$clubMembersSpeeds[$clubActivity['athlete']['id']][] = $record;
			}
		}
		foreach ($clubMembersSpeeds as $clubMemberId => $clubMemberSpeeds) {
			$distanceSum = 0;
			$timeSum = 0;
			foreach ($clubMemberSpeeds as $clubMemberSpeed) {
				$distanceSum += $clubMemberSpeed['distance'];
				$timeSum += $clubMemberSpeed['distance']/$clubMemberSpeed['avgspeed'];
			}
			$clubMembersAvgSpeeds[$clubMemberId] = round($distanceSum/$timeSum, 2);
		}
		//echo '<pre>'.print_r($clubMembersSpeeds, true).'</pre>';
		//echo '<pre>'.print_r($clubMembersAvgSpeeds, true).'</pre>';
		foreach ($clubMembersAvgSpeeds as $athleteId => $avgSpeed) {
			if ($avgSpeed > $this->value) {
				$this->value = $avgSpeed;
				$avgSpeedAthleteId = $athleteId;
			}
		}
		foreach ($clubMembers as $clubMember) {
			if ($clubMember['id'] == $avgSpeedAthleteId) {
				$this->athlete = &$clubMember;
				break;
			}
		}
	}
}