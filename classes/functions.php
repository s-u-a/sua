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

	class Functions
	{
		/**
		* Ueberprueft, ob der richtige Hostname aufgerufen wurde und leitet sonst um.
		*/

		static function checkHostname()
		{
			if(isset($_SERVER['HTTP_HOST']))
			{
				$hostname = $_SERVER['HTTP_HOST'];
				$real_hostname = Config::get_default_hostname();
				if(isset($_SESSION['database']))
				{
					$databases = Config::get_databases();
					if(isset($databases[$_SESSION['database']]) && $databases[$_SESSION['database']]['hostname'])
						$real_hostname = $databases[$_SESSION['database']]['hostname'];
				}

				if($real_hostname)
				{
					$request_uri = $_SERVER['REQUEST_URI'];
					if(strpos($request_uri, '?') !== false)
						$request_uri = substr($request_uri, 0, strpos($request_uri, '?'));

					if(strtolower($hostname) == strtolower($real_hostname) && substr($request_uri, -1) != '/')
						return true;

					$url = global_setting("PROTOCOL").'://'.$real_hostname.$_SERVER['PHP_SELF'];
					if($_SERVER['QUERY_STRING'] != '')
						$url .= '?'.$_SERVER['QUERY_STRING'];
					header('Location: '.$url, true, 307);

					if(count($_POST) > 0)
					{
						echo '<form action="'.htmlspecialchars($url).'" method="post">';
						foreach($_POST as $key=>$val)
							echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'" />';
						echo '<button type="submit">'.htmlspecialchars($url).'</button>';
						echo '</form>';
					}
					else
						echo 'HTTP redirect: <a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a>';
					die();
				}
			}
		}

		########################################

		function array_product($array)
		{
			if(function_exists('array_product'))
				return array_product($array);

			$return = 1;
			foreach($array as $val)
				$return *= $val;
			return $return;
		}

		/**
		* Liefert die Differenz zwischen $ao und $bo zurueck (immer positiv).
		*/

		static function diff($ao, $bo)
		{
			return abs($ao-$bo);
		}

		/**
		* Rundet $a (call by reference) auf $d Stellen nach dem Komma. $d kann auch negativ sein.
		* @return $a
		*/

		static function stdround(&$a, $d=0)
		{
			$f = pow(10, $d);
			$a *= $f;
			$i = floor($a+.5);
			$a = $i/$f;
			return $a;
		}

		/**
		* Wrapper fuer flock(), jedoch mit einem Timeout (1 Sekunde fuer LOCK_SH, sonst 5).
		* @return (booolean) War das Sperren erfolreich?
		*/

		static function fancyFlock($file, $lock_flag)
		{
			if($lock_flag == LOCK_SH) $timeout = 1;
			else $timeout = 15;

			$flag = $lock_flag|LOCK_NB;

			$steps = $timeout*10000;
			for($i=0; $i<100; $i++)
			{
				if(flock($file, $flag)) return true;
				usleep($steps);
			}
			return false;
		}

		/**
		* Verkleinert das Rohstoffarray $array gleichmaessig so, dass dessen Summe den Wert $max nicht uebersteigt.
		*/

		static function fitToMax($array, $max)
		{
			if(!is_array($array) || $max < 0) return false;

			$sum = 0;
			foreach($array as $k=>$v)
			{
				if($v<0) $array[$k] = 0;
				else $sum += $v;
			}

			if($sum > $max)
			{
				$f = $max/$sum;
				$sum = 0;
				global $_fit_to_max_usort;
				$_fit_to_max_usort = array();
				foreach($array as $k=>$v)
				{
					$new_c = $v*$f;
					$fl = ceil($new_c)-$new_c;
					if($fl > 0) $_fit_to_max_usort[$k] = $fl;
					$array[$k] = floor($new_c);
					$sum += $array[$k];
				}

				$remaining = $max-$sum;
				uksort($_fit_to_max_usort, array("Functions", "_fitToMax_usort"));
				while($remaining > 0 && count($_fit_to_max_usort) > 0)
				{
					foreach($_fit_to_max_usort as $k=>$v)
					{
						if($v <= 0) continue;
						$array[$k]++;
						if(--$remaining <= 0) break 2;
					}
				}
			}
			return $array;
		}

		/**
		* Hilfsfunktion fuer fit_to_max().
		*/

		static function _fitToMax_usort($a, $b)
		{
			global $_fit_to_max_usort;

			if($_fit_to_max_usort[$a] > $_fit_to_max_usort[$b]) return -1;
			elseif($_fit_to_max_usort[$a] < $_fit_to_max_usort[$b]) return 1;
			elseif($a > $b) return 1;
			elseif($a < $b) return -1;
			else return 0;
		}

		/**
		* Fuegt an der Zeigerposition im Dateizeiger $fh den String $string ein. Der nachfolgende Inhalt wird nach hinten verschoben.
		* @param $bs Groesse der Bloecke, in denen der Inhalt verschoben wird, in Bytes.
		* @return (boolean) Erfolg
		*/

		static function finsert($fh, $string, $bs=1024)
		{
			if($bs <= 0) return false;
			$pos = ftell($fh);
			$len = strlen($string);

			fseek($fh, 0, SEEK_END);

			$do_break = false;
			while(!$do_break)
			{
				$bytes = $bs;
				if(ftell($fh)-$bytes < $pos)
				{
					$bytes -= $pos-ftell($fh)+$bytes;
					fseek($fh, $pos, SEEK_SET);
				}
				else
					fseek($fh, -$bytes, SEEK_CUR);
				if(ftell($fh) <= $pos)
					$do_break = true;

				$part = fread($fh, $bytes);
				fseek($fh, -$bytes+$len, SEEK_CUR);
				fwrite($fh, $part);
				fseek($fh, -$bytes-$len, SEEK_CUR);
			}
			return fwrite($fh, $string);
		}

		/**
		* Loescht an der Zeigerposition im Dateizeiger $fh die $len Bytes. Der Nachfolgende Inhalt wird vorgezogen.
		* @param $bs Groesse der Bloecke, in denen der Inhalt verschoben wird, in Bytes.
		* @return (boolean) Erfolg
		*/

		static function fdelete($fh, $len, $bs=1024)
		{
			if($bs <= 0) return false;
			$pos = ftell($fh);
			while(true)
			{
				fseek($fh, $len, SEEK_CUR);
				$part = fread($fh, $bs);
				if($part === false) break;
				fseek($fh, -strlen($part)-$len, SEEK_CUR);
				fwrite($fh, $part);
				if(strlen($part) < $bs) break;
			}
			$ret = ftruncate($fh, ftell($fh));
			fseek($fh, $pos, SEEK_SET);
			return $ret;
		}

		/**
		* Liefert einen zufaelligen Index des Arrays $array zurueck. Die Wahrscheinlichkeitenverteilung entspricht den Werten von $array.
		*/

		static function irrand($array)
		{
			$sum = array_sum($array);
			$rand = rand(1, $sum);
			$c_sum = 0;
			foreach($array as $k=>$v)
			{
				$c_sum += $v;
				if($c_sum > $rand)
					return $k;
			}
			return null;
		}

		/**
		* Liefert den GGT von $i und $j zurueck.
		*/

		static function gcd2($i,$j)
		{
			if($i == $j) return $i;
			elseif($i>$j) list($i, $j) = array($j, $i);

			$r = $i%$j;
			while($r != 0)
			{
				$i = $j;
				$j = $r;
				$r = $i%$j;
			}
			return $j;
		}

		/**
		* Liefert den GGT aller Werte des Arrays $a zurueck.
		*/

		static function gcd($a)
		{
			while(($c = count($a)) > 1)
			{
				$b = array();
				for($i=0; $i<$c; $i+=2)
				{
					$o = $a[$i];
					if(isset($a[$i+1])) $p = $a[$i+1];
					else $p = $last;

					$last = gcd2($o, $p);
					$b[] = $last;
				}
				$a = $b;
			}
			if(count($a) == 1) return array_shift($a);
			else return false;
		}

		/**
		* Manuelle Portierung der Tabellen einer SQLite2- in eine SQLite3-Datenbank.
		*/

		static function sqlite2sqlite3($old_fname, $new_fname)
		{
			$old_db = new PDO("sqlite2:".$old_fname);
			$old_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$new_db = new PDO("sqlite:".$new_fname);
			$new_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$tables = array();
			$master_query = $old_db->query("SELECT * FROM sqlite_master;");
			while(($res = $master_query->fetch(PDO::FETCH_ASSOC)) !== false)
			{
				if($res['sql'])
					$new_db->query($res['sql']);
				if($res['type'] == "table")
					$tables[] = $res['name'];
			}

			foreach($tables as $table)
			{
				$data_query = $old_db->query("SELECT * FROM ".$table.";");
				while(($res = $data_query->fetch(PDO::FETCH_ASSOC)) !== false)
				{
					foreach($res as $k=>$v)
						$res[$k] = $new_db->quote($v);
					$new_db->query("INSERT INTO ".$table." ( ".implode(", ", array_keys($res)).") VALUES ( ".implode(", ", array_values($res))." );");
				}
			}
		}

		/**
		* Liefert den Index zurueck, unter dem in $arr der groesste Wert gespeichert ist.
		*/

		static function max_index($arr)
		{
			$max = null;
			$index = null;

			foreach($arr as $k=>$v)
			{
				if($v === null || is_array($v) || is_object($v)) continue;

				if($max === null || $v > $max)
				{
					$max = $v;
					$index = $k;
				}
			}

			return $index;
		}

		/**
		* Liefert den Index zurueck, unter dem in $arr der kleinste Wert gespeichert ist.
		*/

		static function min_index($arr)
		{
			$min = null;
			$index = null;

			foreach($arr as $k=>$v)
			{
				if($v === null || is_array($v) || is_object($v)) continue;

				if($min === null || $v < $min)
				{
					$min = $v;
					$index = $k;
				}
			}

			return $index;
		}

		/**
		* Kodiert ein Rohstoff-Array zu einem String. Format: Menge1 ' ' Menge 2 ' ' ...
		*/

		static function encodeRessList($list)
		{
			return implode(" ", $list);
		}

		/**
		* Konvertiert einen mit encode_ress_list() kodierten String zurueck zu einem Rohstoff-Array.
		*/

		static function decodeRessList($encoded)
		{
			$list = (strlen($encoded) > 0 ? explode(" ", $encoded) : array());
			foreach($list as $k=>$v)
				$list[$k] = (float) $v;
			return $list;
		}

		/**
		* Fügt in der Zahl $number der Ziffer Nummer $digit $change hinzu. Wird in der Zahl 555 der ersten Ziffer ($digit = 2) $change = 7 addiert, so erhält man 255.
		* @param $number integer Die Zahl, die geändert werden soll.
		* @param $digit integer Die wievielte Ziffer soll geändert werden? 0 ist ganz rechts. Negative Werte möglich.
		* @param $change integer Wieviel soll der Ziffer hinzugefügt werden? Negative Werte möglich.
		* @return integer Die neue Zahl.
		*/

		static function change_digit($number, $digit, $change)
		{
			$d = floor(($number%pow(10, $digit+1))/pow(10, $digit));
			$d_new = $d+$change;
			while($d_new >= 10) $d_new -= 10;
			while($d_new < 0) $d_new += 10;
			return $number += ($d_new-$d)*pow(10, $digit);
		}

		/**
		* Funktioniert wie explode(), liefert aber ein leeres Array bei einem leeren String zurück.
		*/

		static function explode0($delimiter, $string, $limit=null)
		{
			if(strlen($string) > 0)
			{
				if(isset($limit))
					return explode($delimiter, $string, $limit);
				else
					return explode($delimiter, $string);
			}
			return array();
		}

		/**
		* Überprüft die E-Mail-Adresse $email auf Gültigkeit.
		*/

		static function check_email($email)
		{
			$reg = "(^((([A-Za-z0-9.!#$%&'*+-/=?^_`{|}~]|\\\\.){1,64})|(\"([\\x00-\\x21\\x23-\\x5b\\x5d-\\x7f]|\\\\[\\\\\"]){1,64}\"))@((([a-zA-Z0-9][-a-zA-Z0-9]*)?[a-zA-Z0-9]\\.)*(([a-zA-Z0-9][-a-zA-Z0-9]*)?[a-zA-Z0-9]))\$)";
			return preg_match($reg, $email);
		}

		/**
		* Debug-Ausgabe im Format $string:Zeit:wievielter Aufruf mit $string:Zeit seit letztem Aufruf mit $string:wievielter Aufruf überhaupt:Zeit seit letztem Aufruf überhaupt
		*/

		static function debug_time($string="", $max=null)
		{
			static $times;
			if(!isset($times)) $times = array();
			$time = round(microtime(true)*1000, 3);
			if($max === null || !isset($times[$string]) || $times[$string][0] <= $max)
			{
				echo htmlspecialchars($string).":".$time;
				if(isset($times[$string]))
					echo ":".$times[$string][0].":".($time-$times[$string][1]);
				if(isset($times[""]))
					echo ":".$times[""][0].":".($time-$times[""][1]);
				echo "<br />\n";
			}
			if(!isset($times[$string])) $times[$string] = array(0);
			if(!isset($times[""])) $times[""] = array(0);
			$times[$string][0]++;
			$times[$string][1] = $time;
			$times[""][0]++;
			$times[""][1] = $time;
		}
	}