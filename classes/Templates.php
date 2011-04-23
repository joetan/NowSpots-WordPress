<?php
class NowSpots_Templates {
	protected $className = 'NowSpots_Templates';

	public function getAll() {
		return array(
			'box' => 'Box 300x250',
			'leaderboard' => 'Leaderboard 728x90',
			'skyscraper' => 'Skyscraper 160x600',
		);
	}
}