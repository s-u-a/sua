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
	/**
	 * @author Candid Dauth
	 * @package sua
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Erweitert die PHP-Standardfunktionen um nützliche Dinge.
	*/

	class Functions
	{
		/**
		 * Liefert das Produkt der Array-Werte zurück.
		 * @param array(float) $array
		 * @return float
		*/

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
		 * @param float $ao
		 * @param float $bo
		 * @return float
		*/

		static function diff($ao, $bo)
		{
			return abs($ao-$bo);
		}

		/**
		 * Rundet $a (call by reference) auf $d Stellen nach dem Komma. $d kann auch negativ sein.
		 * @param float $a
		 * @param int $d
		 * @return float $a
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
		 * @param resource $file
		 * @param int $lock_flag LOCK_*
		 * @return bool War das Sperren erfolreich?
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
		 * @param array(int) $array
		 * @param int $max
		 * @return array(int)
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
		 * @param int $a
		 * @param int $b
		 * @return int
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
		 * @param resource $fh
		 * @param string $string
		 * @param int $bs Groesse der Bloecke, in denen der Inhalt verschoben wird, in Bytes.
		 * @return bool Erfolg
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
		 * @param resurce $fh
		 * @param int $len
		 * @param int $bs Groesse der Bloecke, in denen der Inhalt verschoben wird, in Bytes.
		 * @return bool Erfolg
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
		 * Liefert einen zufaelligen Index des Arrays $array zurueck.
		 * Die Wahrscheinlichkeitenverteilung entspricht den Werten von $array.
		 * @param array $array
		 * @return int
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
		 * @param int $i
		 * @param int $j
		 * @return int
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
		 * @param array(int) $a
		 * @return int
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
		 * @param string $old_fname
		 * @param string $new_fname
		 * @return void
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
		 * @param array(float) $arr
		 * @return int|string
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
		 * @param array(float) $arr
		 * @return int|string
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
		 * @param array $list
		 * @return string
		*/

		static function encodeRessList($list)
		{
			return implode(" ", $list);
		}

		/**
		 * Konvertiert einen mit encodeRessList() kodierten String zurueck zu einem Rohstoff-Array.
		 * @param string $encoded
		 * @return array
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
		 * @param int $number Die Zahl, die geändert werden soll.
		 * @param int $digit Die wievielte Ziffer soll geändert werden? 0 ist ganz rechts. Negative Werte möglich.
		 * @param int $change Wieviel soll der Ziffer hinzugefügt werden? Negative Werte möglich.
		 * @return int Die neue Zahl.
		*/

		static function changeDigit($number, $digit, $change)
		{
			$d = floor(($number%pow(10, $digit+1))/pow(10, $digit));
			$d_new = $d+$change;
			while($d_new >= 10) $d_new -= 10;
			while($d_new < 0) $d_new += 10;
			return $number += ($d_new-$d)*pow(10, $digit);
		}

		/**
		 * Funktioniert wie explode(), liefert aber ein leeres Array bei einem leeren String zurück.
		 * @param string $delimiter
		 * @param string $string
		 * @param int $limit
		 * @return array(string)
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
		 * @param string $email
		 * @return bool
		*/

		static function check_email($email)
		{
			$reg = "(^((([A-Za-z0-9.!#$%&'*+-/=?^_`{|}~]|\\\\.){1,64})|(\"([\\x00-\\x21\\x23-\\x5b\\x5d-\\x7f]|\\\\[\\\\\"]){1,64}\"))@((([a-zA-Z0-9][-a-zA-Z0-9]*)?[a-zA-Z0-9]\\.)*(([a-zA-Z0-9][-a-zA-Z0-9]*)?[a-zA-Z0-9]))\$)";
			return (true && preg_match($reg, $email));
		}

		/**
		 * Debug-Ausgabe im Format $string:Zeit:wievielter Aufruf mit $string:Zeit seit letztem Aufruf mit $string:wievielter Aufruf überhaupt:Zeit seit letztem Aufruf überhaupt
		 * @param string $string
		 * @param int $max
		 * @return void
		*/

		static function debugTime($string="", $max=null)
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

		/**
		 * Gibt den ersten Index des Arrays $array zurück.
		 * @param array $array
		 * @return int|string
		*/

		static function first($array)
		{
			$keys = array_keys($array);
			if(!isset($keys[0])) return null;
			return $keys[0];
		}

		/**
		 * GIbt des letzten Index des Arrays $array zurück.
		 * @param array $array
		 * @return int|string
		*/

		static function last($array)
		{
			$keys = array_keys($array);
			if(count($keys) < 1) return null;
			return $keys[count($keys)-1];
		}

		/**
		 * Gibt eine mögliche neue ID für ein Dataset zurück. Es ist nicht sichergestellt,
		 * dass diese nicht bereits existiert. Liefert jedesmal eine andere ID zurück.
		 * @return string
		*/

		static function randomID()
		{
			return str_replace(".", "-", microtime(true));
		}

		/**
		 * Gibt die Summe der Summen der Arrays im Array $array zurück.
		 * @param array $array Array von Arrays von Zahlen
		 * @return float
		*/

		function array_sum_r(array $array)
		{
			$sum = 0;
			foreach($array as $val)
				$sum += array_sum($val);
			return $sum;
		}

		/**
		 * Konvertiert einen vom Benutzer eingegebenen String in einen Boolean-Wert. Werte wie „no“ oder „false“
		 * werden zu false.
		 * @param string $string
		 * @throw UnexpectedValueException Der übergebene Wert wurde nicht erkannt.
		 * @return boolean
		*/

		function string2boolean($string)
		{
			if(in_array(strtolower($string), array("yes", "y", "1", "true", "on", "enabled", "enable")))
				return true;
			elseif(!$string || in_array(strtolower($string), array("no", "n", "false", "off", "disabled", "disable")))
				return false;
			else
				throw UnexpectedValueException("String could not be converted to a boolean.");
		}
	}