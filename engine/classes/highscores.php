<?php
	class Highscores extends SQLite
	{
		protected $tables = array("highscores_users" => array("username", "alliance", "scores INT", "changed INT"), "highscores_alliances" => array("tag", "scores_average INT", "scores_total INT", "members_count INT", "changed INT"));

		function updateUser($username, $alliance=false, $scores=false)
		{
			if(!$this->status) return false;

			$this->query("SELECT username FROM highscores_users WHERE username='".$this->escape($username)."' LIMIT 1;");
			$exists = ($this->lastResultCount() > 0);

			if($scores !== false) $scores = (float) $scores;

			if($exists)
			{
				if($alliance === false && $scores === false) return true;

				$query = "UPDATE highscores_users SET ";
				$set = array();
				if($alliance !== false) $set[] = "alliance = '".$this->escape($alliance)."'";
				if($scores !== false)
				{
					$set[] = "scores = '".$this->escape($scores)."'";
					$set[] = "changed = '".$this->escape(microtime(true))."'";
				}
				$query .= implode(', ', $set);
				$query .= " WHERE username = '".$this->escape($username)."';";
			}
			else
			{
				$scores = (float) $scores;
				$query = "INSERT INTO highscores_users ( username, alliance, scores, changed ) VALUES ( '".$this->escape($username)."', '".$this->escape($alliance)."', '".$this->escape($scores)."', '".$this->escape(microtime(true))."' );";
			}

			return $this->query($query);
		}

		function renameUser($old_username, $new_username)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE highscores_users SET username = '".$this->escape($new_username)."' WHERE username = '".$this->escape($old_username)."';");
		}

		function renameAlliance($old_alliance, $new_alliance)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE highscores_alliances SET tag = '".$this->escape($new_alliance)."' WHERE tag = '".$this->escape($old_alliance)."';");
		}

		function updateAlliance($tag, $scores_average=false, $scores_total=false, $members_count=false)
		{
			if(!$this->status) return false;

			$exists_query = $this->query("SELECT tag FROM highscores_alliances WHERE tag='".$this->escape($tag)."' LIMIT 1;");
			$exists = ($this->lastResultCount() > 0);

			if($exists)
			{
				if($scores_average === false && $scores_total === false && $members_count === false) return true;

				$query = "UPDATE highscores_alliances SET ";
				$set = array();
				if($scores_average !== false) $set[] = "scores_average = '".$this->escape($scores_average)."'";
				if($scores_total !== false) $set[] = "scores_total = '".$this->escape($scores_total)."'";
				if($members_count !== false) $set[] = "members_count = '".$this->escape($members_count)."'";
				$query .= implode(', ', $set);
				$query .= " WHERE tag = '".$this->escape($tag)."';";
			}
			else $query = "INSERT INTO highscores_alliances ( tag, scores_average, scores_total, members_count ) VALUES ( '".$this->escape($tag)."', '".$this->escape($scores_average)."', '".$this->escape($scores_total)."', '".$this->escape($members_count)."' );";

			return $this->query($query);
		}

		function removeEntry($type, $id)
		{
			if(!$this->status || ($type != 'users' && $type != 'alliances')) return false;

			if($type == 'users') $index = 'username';
			else $index = 'tag';

			return $this->query("DELETE FROM highscores_".$type." WHERE ".$index." = '".$this->escape($id)."';");
		}

		function getList($type, $from, $to, $sort_field=false)
		{
			if(!$this->status || ($type != 'users' && $type != 'alliances')) return false;

			$allowed_sort_fields = array(
				'alliances' => array('scores_average', 'scores_total'),
				'users' => array('scores')
			);

			if($sort_field === false) $sort_field = array_shift($allowed_sort_fields[$type]);
			elseif(!in_array($sort_field, $allowed_sort_fields[$type])) return false;

			if($from > $to) list($from, $to) = array($to, $from);
			$from--;

			return $this->arrayQuery("SELECT * FROM highscores_".$type." ORDER BY ".$sort_field." DESC,changed ASC LIMIT ".$from.", ".($to-$from-1).";");
		}

		function getCount($type, $highscores_file=false)
		{
			if($type != 'users' && $type != 'alliances') return false;

			return $this->singleField("SELECT count(*) FROM highscores_".$type.";");
		}

		function getPosition($type, $id, $sort_field=false)
		{
			if(!$this->status || ($type != 'users' && $type != 'alliances')) return false;

			if($type == 'users') $index = 'username';
			else $index = 'tag';

			$allowed_sort_fields = array(
				'alliances' => array('scores_average', 'scores_total'),
				'users' => array('scores')
			);

			if($sort_field === false) $sort_field = array_shift($allowed_sort_fields[$type]);
			elseif(!in_array($sort_field, $allowed_sort_fields[$type])) return false;

			# Zuerst Punkte herausfinden
			$r =  $this->singleQuery("SELECT ".$sort_field.",changed FROM highscores_".$type." WHERE ".$index." = '".$this->escape($id)."' LIMIT 1;");
			$scores = $r[$sort_field];
			$changed = $r['changed'];

			# Wieviele Spieler sind von den Punkten her darueber?
			$above = $this->singleField("SELECT COUNT(*) FROM highscores_".$type." WHERE ".$sort_field." > '".$this->escape($scores)."';");

			# Wieviele Spieler haben die gleiche Punktzahl, aber hatten diese frueher?
			$above += $this->singleField("SELECT COUNT(*) FROM highscores_".$type." WHERE ".$sort_field." = '".$this->escape($scores)."' AND changed < '".$this->escape($changed)."';");

			return ($above+1);
		}

		function destroy()
		{
			if(!$this->status) return false;

			return ($this->query("DELETE FROM highscores_users;") && $this->query("DELETE FROM highscores_alliances;"));
		}
	}
?>
