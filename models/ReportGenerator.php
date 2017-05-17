<?php
namespace StravaStat\ReportGenerator;

class ReportGenerator {
	private $timestamp;
	
	public function __construct() {
		$this->timestamp = time();
	}
	
	public function setTimestamp(int $timestamp) {
		$this->timestamp = $timestamp;
	}
	
	public function getLastWeekRange(): array {
		$d = time() - 86400*7;
		$start_week = strtotime('last sunday midnight', $d) + 86400;
		$end_week = strtotime('next saturday', $d) + 86400 + 86400 - 1;
		return [$start_week, $end_week];
	}
	
	public function getCurrentWeekRange(): array {
		$start_week = strtotime('last sunday midnight', time()) + 86400;
		$end_week = $this->timestamp;
		return [$start_week, $end_week];
	}
	
	public function getWeekRange(int $timestamp): array {
		$start_week = strtotime('last sunday midnight', $timestamp) + 86400;
		$end_week = strtotime('next saturday', $timestamp) + 86400 + 86400 - 1;
		return [$start_week, $end_week];
	}
	
	public function getWeekRangeByDate(string $date): array {
		return $this->getWeekRange(strtotime($date));
	}
	
	public function inRange(int $timestamp, array $range): bool {
		return ($timestamp >= $range[0] && $timestamp <= $range[1]);
	}
	
	public function createRange($from, $to) {
		$period = [
			strtotime($from),
			strtotime($to) + 86400 - 1
		];
		return $period;
	}
}