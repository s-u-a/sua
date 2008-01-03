<?php
	class Highscores extends SQLite
	{
		protected $tables = array("highscores_users" => array("username", "alliance", "scores_0 INT", "scores_1 INT", "scores_2 INT", "scores_3 INT", "scores_4 INT", "scores_5 INT", "scores_6 INT", "changed INT"), "highscores_alliances" => array("tag", "scores_average INT", "scores_total INT", "members_count INT", "changed INT"));

		function updateUser($username, $alliance=false, $scores=null)
		{
			if(!$this->status) return false;

			$exists = ($this->singleField("SELECT COUNT(*) FROM highscores_users WHERE username=".$this->escape($username)." LIMIT 1;") > 0);

			if($scores === null)
				$scores = array();
			for($i=0; $i<=6; $i++)
			{
				if(!isset($scores[$i])) $scores[$i] = null;
			}

			if($exists)
			{
				if($alliance === false && $scores === false) return true;

				$query = "UPDATE highscores_users SET ";
				$set = array();
				if($alliance !== false) $set[] = "alliance = ".$this->escape($alliance);
				$scores_changed = false;
				for($i=0; $i<=6; $i++)
				{
					if($scores[$i] !== null)
					{
						$set[] = "scores_".$i." = ".$this->escape($scores[$i]);
						$scores_changed = true;
					}
				}
				if($scores_changed)
					$set[] = "changed = ".$this->escape(microtime(true));
				$query .= implode(', ', $set);
				$query .= " WHERE username = ".$this->escape($username).";";
			}
			else
				$query = "INSERT INTO highscores_users ( username, alliance, scores_0, scores_1, scores_2, scores_3, scores_4, scores_5, scores_6, changed ) VALUES ( ".$this->escape($username).", ".$this->escape($alliance).", ".$this->escape($scores[0]).", ".$this->escape($scores[1]).", ".$this->escape($scores[2]).", ".$this->escape($scores[3]).", ".$this->escape($scores[4]).", ".$this->escape($scores[5]).", ".$this->escape($scores[6]).", ".$this->escape(microtime(true))." );";

			return $this->query($query);
		}

		function renameUser($old_username, $new_username)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE highscores_users SET username = ".$this->escape($new_username)." WHERE username = ".$this->escape($old_username).";");
		}

		function renameAlliance($old_alliance, $new_alliance)
		{
			if(!$this->status) return false;

			return $this->query("UPDATE highscores_alliances SET tag = ".$this->escape($new_alliance)." WHERE tag = ".$this->escape($old_alliance).";");
		}

		function updateAlliance($tag, $scores_average=false, $scores_total=false, $members_count=false)
		{
			if(!$this->status) return false;

			$exists = ($this->singleField("SELECT COUNT(*) FROM highscores_alliances WHERE tag=".$this->escape($tag)." LIMIT 1;") > 0);

			if($exists)
			{
				if($scores_average === false && $scores_total === false && $members_count === false) return true;

				$query = "UPDATE highscores_alliances SET ";
				$set = array();
				if($scores_average !== false) $set[] = "scores_average = ".$this->escape($scores_average);
				if($scores_total !== false) $set[] = "scores_total = ".$this->escape($scores_total);
				if($members_count !== false) $set[] = "members_count = ".$this->escape($members_count);
				$query .= implode(', ', $set);
				$query .= " WHERE tag = ".$this->escape($tag).";";
			}
			else $query = "INSERT INTO highscores_alliances ( tag, scores_average, scores_total, members_count ) VALUES ( ".$this->escape($tag).", ".$this->escape($scores_average).", ".$this->escape($scores_total).", ".$this->escape($members_count)." );";

			return $this->query($query);
		}

		function removeEntry($type, $id)
		{
			if(!$this->status || ($type != 'users' && $type != 'alliances')) return false;

			if($type == 'users') $index = 'username';
			else $index = 'tag';

			return $this->query("DELETE FROM highscores_".$type." WHERE ".$index." = ".$this->escape($id).";");
		}

		function getList($type, $from, $to, $sort_field=null, $score_fields=null)
		{
			if(!$this->status || ($type != 'users' && $type != 'alliances')) return false;

			if($from > $to) list($from, $to) = array($to, $from);
			$from--;

			if($type == "users")
			{
				if(!is_array($score_fields))
					$score_fields = array($score_fields);
				foreach($score_fields as $k=>$v)
				{
					if($v === null)
						$score_fields[$k] = "scores_0+scores_1+scores_2+scores_3+scores_4+scores_5+scores_6";
				}
				if($sort_field === null)
					$sort_field = "scores_0+scores_1+scores_2+scores_3+scores_4+scores_5+scores_6";
				return $this->arrayQuery("SELECT username, alliance, ".implode(", ", array_unique($score_fields))." FROM highscores_users ORDER BY ".$sort_field." DESC,changed ASC LIMIT ".$from.", ".($to-$from-1).";");
			}
			else
			{
				if($sort_field === null)
					$sort_field = "scores_average";
				return $this->arrayQuery("SELECT tag, scores_average, scores_total, members_count FROM highscores_".$type." ORDER BY ".$sort_field." DESC,changed ASC LIMIT ".$from.", ".($to-$from-1).";");
			}
		}

		function getCount($type)
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
				'users' => array("scores_0+scores_1+scores_2+scores_3+scores_4+scores_5+scores_6", "scores_0", "scores_1", "scores_2", "scores_3", "scores_4", "scores_5", "scores_6")
			);

			if($sort_field === false) $sort_field = array_shift($allowed_sort_fields[$type]);
			elseif(!in_array($sort_field, $allowed_sort_fields[$type])) return false;

			# Zuerst Punkte herausfinden
			$r =  $this->singleQuery("SELECT ".$sort_field.",changed FROM highscores_".$type." WHERE ".$index." = ".$this->escape($id)." LIMIT 1;");
			$scores = $r[$sort_field];
			$changed = $r['changed'];

			# Wieviele Spieler sind von den Punkten her darueber?
			$above = $this->singleField("SELECT COUNT(*) FROM highscores_".$type." WHERE ".$sort_field." > ".$this->escape($scores).";");

			# Wieviele Spieler haben die gleiche Punktzahl, aber hatten diese frueher?
			$above += $this->singleField("SELECT COUNT(*) FROM highscores_".$type." WHERE ".$sort_field." = ".$this->escape($scores)." AND changed < ".$this->escape($changed).";");

			return ($above+1);
		}

		function destroy()
		{
			if(!$this->status) return false;

			return ($this->query("DELETE FROM highscores_users;") && $this->query("DELETE FROM highscores_alliances;"));
		}
	}
