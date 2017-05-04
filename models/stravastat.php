<?php
class StravaStat {
	public function convertSpeed($speed) {
		$speed = round((float)$speed, 2);
		return round($speed / 0.2777777777777778, 2);
	}
	
	public function converDistance($distance) {
		$distance = round((float)$distance, 2);
		return round($distance / 1000, 2);
	}
	
	public function convertTime($time) {
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
}