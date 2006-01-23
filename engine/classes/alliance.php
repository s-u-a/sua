<?php
	class Alliance extends Dataset
	{
		protected $save_dir = DB_ALLIANCES;
		private $datatype = 'alliance';
		
		function recalcHighscores()
		{
		}
		
		function setUserScores($user, $scores)
		{
		}
		
		protected function getDataFromRaw(){}
		protected function getRawFromData(){}
	}
?>