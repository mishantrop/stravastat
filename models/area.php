<?php
namespace StravaStat;

class Area {
	private $startLat;
	private $startLng;
	private $endLat;
	private $endLng;
	
	public function _construct() {
		// From left bottom to right top
		$this->startLat = 0.0;
		$this->startLng = 0.0;
		$this->endLat = 0.0;
		$this->endLng = 0.0;
	}
	
	public function setStartLat(float $lat) {
		$this->startLat = round($lat, 2);
	}
	
	public function setStartLng(float $lng) {
		$this->startLng = round($lng, 2);
	}
	
	public function setEndLat(float $lat) {
		$this->endLat = round($lat, 2);
	}
	
	public function setEndLng(float $lng) {
		$this->endLng = round($lng, 2);
	}
	
	public function matchToArea(float $lat, float $lng) {
		return ($lat > $this->startLat && $lat < $this->endLat && $lng > $this->startLng && $lng < $this->endLng);
	}
}