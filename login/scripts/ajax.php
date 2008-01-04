<?php
/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/
	$LOGIN = true;
	define('ignore_action', true);
	define('ajax', true);
	require_once('../../engine/include.php');

	header('Content-type: text/xml;charset=UTF-8');
	echo "<xmlresponse>\n";

	echo <<<EOF
<!--
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
-->

EOF;

	if(!isset($_GET['action'])) $_GET['action'] = null;
	else
	{
		$databases = get_databases();
		if(!isset($_GET['database']) || !isset($databases[$_GET['database']])) $_GET['action'] = false;
		else define_globals($_GET['database']);
	}

	switch($_GET['action'])
	{
		case 'userlist':
			$query = '';
			if(isset($_GET['query']))
				$query = strtolower(urlencode($_GET['query']));
			$query_length = strlen($query);
			if($query_length < global_setting("LIST_MIN_CHARS")) break;

			$results = array();
			$dh = opendir(global_setting("DB_PLAYERS"));
			while(($fname = readdir($dh)) !== false)
			{
				if($fname == '.' || $fname == '..') continue;
				$fname = $fname;

				if(strlen($fname) >= $query_length && substr($fname, 0, $query_length) == $query)
					$results[] = urldecode($fname);
			}
			closedir($dh);

			natcasesort($results);

			foreach($results as $result)
				echo "\t<result>".htmlspecialchars(urldecode($result))."</result>\n";

			break;

		case 'spionage': case 'besiedeln': case 'sammeln': case 'shortcut':
			list($classname, $result) = include('../flotten.php');
			echo "\t<classname>".htmlspecialchars($classname)."</classname>\n";
			echo "\t<result>".htmlspecialchars($result)."</result>\n";
			break;

		case 'universe':
			if(!isset($_GET['system']) || !is_array($_GET['system'])) $_GET['system'] = array();
			foreach($_GET['system'] as $systemo)
			{
				$system = explode(':', $systemo);
				if(count($system) != 2) continue;
				$galaxy_obj = Classes::Galaxy($system[0]);
				if(!$galaxy_obj->getStatus() || !($planets_count = $galaxy_obj->getPlanetsCount($system[1])))
					continue;
				echo "\t<system number=\"".htmlspecialchars($systemo)."\">\n";

				for($i=1; $i<=$planets_count; $i++)
				{
					echo "\t\t<planet number=\"".htmlspecialchars($i)."\">\n";
					echo "\t\t\t<owner>".htmlspecialchars($galaxy_obj->getPlanetOwner($system[1], $i))."</owner>\n";
					echo "\t\t\t<name>".htmlspecialchars($galaxy_obj->getPlanetName($system[1], $i))."</name>\n";
					echo "\t\t\t<alliance>".htmlspecialchars($galaxy_obj->getPlanetOwnerAlliance($system[1], $i))."</alliance>\n";
					echo "\t\t\t<flag>".htmlspecialchars($galaxy_obj->getPlanetOwnerFlag($system[1], $i))."</flag>\n";
					$truemmerfeld = truemmerfeld::get($system[0], $system[1], $i);
					if(array_sum($truemmerfeld) > 0)
						echo "\t\t\t<truemmerfeld carbon=\"".htmlspecialchars($truemmerfeld[0])."\" aluminium=\"".htmlspecialchars($truemmerfeld[1])."\" wolfram=\"".htmlspecialchars($truemmerfeld[2])."\" radium=\"".htmlspecialchars($truemmerfeld[3])."\" />\n";
					echo "\t\t</planet>\n";
				}

				echo "\t</system>\n";
			}
	}

	echo "</xmlresponse>\n";
?>