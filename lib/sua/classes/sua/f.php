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
	 * Stellt statische Funktionen zur Verfügung, die Werte zur Ausgabe an den
	 * Benutzer formatieren.
	*/

	class F
	{
		/**
		 * Formatiert eine Bauzeitangabe zu einem menschlich lesbaren Format.
		 * Beispiel: 650 wird zu 10 Minuten, 50 Sekunden
		 * @param int $time2 Bauzeit in Sekunden
		 * @param bool $short Sollen Werte, die 0 sind, weggelassen werden (Beispiel: 5 Minuten, 0 Sekunden)
		 * @return string
		*/

		static function formatBTime($time2, $short=false)
		{
			# Formatiert eine in Punkten angegebene Bauzeitangabe,
			# sodass diese auf den Seiten angezeigt werden kann
			# (zum Beispiel 2 Stunden, 5 Minuten und 30 Sekunden)

			$time = round($time2);
			$days = $hours = $minutes = $seconds = 0;

			if($time >= 86400)
			{
				$mod = $time%86400;
				$days = ($time-$mod)/86400;
				$time = $mod;
			}
			if($time >= 3600)
			{
				$mod = $time%3600;
				$hours = ($time-$mod)/3600;
				$time = $mod;
			}
			if($time >= 60)
			{
				$mod = $time%60;
				$minutes = ($time-$mod)/60;
				$time = $mod;
			}
			$seconds = $time;

			$return = array();
			if($time2 >= 86400 && (!$short || $days != 0))
				$return[] = sprintf(ngettext("%s Tag", "%s Tage", $days), $days);
			if($time2 >= 3600 && (!$short || $hours != 0))
				$return[] = sprintf(ngettext("%s Stunde", "%s Stunden", $hours), $hours);
			if($time2 >= 60 && (!$short || $minutes != 0))
				$return[] = sprintf(ngettext("%s Minute", "%s Minuten", $minutes), $minutes);
			if(!$short || $seconds != 0)
				$return[] = sprintf(ngettext("%s Sekunde", "%s Sekunden", $seconds), $seconds);

			$return = L::h(implode(' ', $return));
			return $return;
		}

		/**
		 * Formatiert die angegeben Rohstoffmenge zu einem menschlich lesbaren Format.
		 * @param array $ress Ein Array mit den Rohstoffmengen als Werte
		 * @param int $tabs_count Die Anzahl der einzurueckenden Tabs des HTML-Codes
		 * @param bool $tritium Soll der Array-Wert 4 beachtet werden (Tritium)
		 * @param bool $energy Soll der Array-Wert 5 beachtet werden (Energie)
		 * @param bool $_i Die Ausgabe wird so formatiert, dass sie nachtraeglich durch _i() gejagt werden kann
		 * @param Planet $check_availability Gibt mit HTML-Klassen an, ob so viele Rohstoffe auf dem Planeten vorhanden sind.
		 * @param string $dl_class Eine zusätzliche HTML-Klasse, die dem Element zugewiesen wird.
		 * @param string $dl_id Eine HTML-ID, die der Liste zugewiesen wird.
		 * @param Planet $check_limit Gibt mit HTML-Klassen an, ob die Rohstoffmenge die Speicher übersteigt
		 * @return string Eine den HTML-Code einer dl-Liste mit den formatierten Rohstoffangaben
		*/

		static function formatRess($ress, $tabs_count=0, $tritium=false, $energy=false, $_i=false, $check_availability=null, $dl_class="inline", $dl_id=null, $check_limit=null)
		{
			# Erstellt eine Definitionsliste aus der uebergebenen
			# Rohstoffanzahl, beispielsweise fuer die Rohstoffkosten
			# der Gebaeude verwendbar

			$tabs = '';
			if($tabs_count >= 1)
				$tabs = str_repeat("\t", $tabs_count);

			$class = array("", "", "", "", "", "", "");
			if($check_availability)
			{
				$res_avail = $check_availability->getRess();
				$class[0] .= ($res_avail[0]<$ress[0])?" ress-fehlend":"";
				$class[1] .= ($res_avail[1]<$ress[1])?" ress-fehlend":"";
				$class[2] .= ($res_avail[2]<$ress[2])?" ress-fehlend":"";
				$class[3] .= ($res_avail[3]<$ress[3])?" ress-fehlend":"";
				if($tritium) $class[4] .= ($res_avail[4]<$ress[4])?" ress-fehlend":"";
				if($energy) $class[5] .= ($res_avail[5]<$ress[5])?" ress-fehlend":"";
			}
			if($check_limit)
			{
				$res_limit = $check_limit->getProductionLimit();
				$class[0] = ($res_limit[0]<$ress[0])?" speicher-voll":"";
				$class[1] = ($res_limit[1]<$ress[1])?" speicher-voll":"";
				$class[2] = ($res_limit[2]<$ress[2])?" speicher-voll":"";
				$class[3] = ($res_limit[3]<$ress[3])?" speicher-voll":"";
				if($tritium) $class[4] = ($res_limit[4]<$ress[4])?" speicher-voll":"";
				if($energy)
				{
					$prod = $check_limit->getProduction();
					$class[5] = ($prod[7])?" speicher-voll":"";
				}
			}
			$class[0] .= ($ress[0]<0)?" ress-negativ":"";
			$class[1] .= ($ress[1]<0)?" ress-negativ":"";
			$class[2] .= ($ress[2]<0)?" ress-negativ":"";
			$class[3] .= ($ress[3]<0)?" ress-negativ":"";
			if($tritium) $class[4] .= ($ress[4]<0)?" ress-negativ":"";
			if($energy) $class[5] .= ($ress[5]<0)?" ress-negativ":"";

			$return = $tabs."<dl class=\"ress ".htmlspecialchars($dl_class)."\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."\"" : "").">\n";
			$return .= $tabs."\t<dt class=\"ress-carbon".$class[0]."\">".($_i ? "[ress_0]" : L::h(_("[ress_0]")))."</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-carbon".$class[0]."\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."-carbon\"" : "").">".F::ths($ress[0])."</dd>\n";
			$return .= $tabs."\t<dt class=\"ress-aluminium".$class[1]."\">".($_i ? "[ress_1]" : L::h(_("[ress_1]")))."</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-aluminium".$class[1]."\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."-aluminium\"" : "").">".F::ths($ress[1])."</dd>\n";
			$return .= $tabs."\t<dt class=\"ress-wolfram".$class[2]."\">".($_i ? "[ress_2]" : L::h(_("[ress_2]")))."</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-wolfram".$class[2]."\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."-wolfram\"" : "").">".F::ths($ress[2])."</dd>\n";
			$return .= $tabs."\t<dt class=\"ress-radium".$class[3]."".($tritium ? "" : " ress-last")."\">".($_i ? "[ress_3]" : L::h(_("[ress_3]")))."</dt>\n";
			$return .= $tabs."\t<dd class=\"ress-radium".$class[3]."".($tritium ? "" : " ress-last")."\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."-radium\"" : "").">".F::ths($ress[3])."</dd>\n";
			if($tritium)
			{
				$return .= $tabs."\t<dt class=\"ress-tritium".$class[4]."".($energy ? "" : " ress-last")."\">".($_i ? "[ress_4]" : L::h(_("[ress_4]")))."</dt>\n";
				$return .= $tabs."\t<dd class=\"ress-tritium".$class[4]."".($energy ? "" : " ress-last")."\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."-tritium\"" : "").">".F::ths($ress[4])."</dd>\n";
			}
			if($energy)
			{
				$return .= $tabs."\t<dt class=\"ress-energie".$class[5]." ress-last\">".($_i ? "[ress_5]" : L::h(_("[ress_5]")))."</dt>\n";
				$return .= $tabs."\t<dd class=\"ress-energie".$class[5]." ress-last\"".($dl_id !== null ? " id=\"".htmlspecialchars($dl_id)."-energy\"" : "").">".F::ths($ress[5])."</dd>\n";
			}
			$return .= $tabs."</dl>\n";
			return $return;
		}

		/**
		 * Formatiert eine Zahl in ein lesbares Format.
		 * @param float $count Die zu formatierende Zahl
		 * @param null $utf8 Ohne Auswirkungen, Kompatiblitaetsparameter
		 * @param int $round Anzahl der zu rundenden Stellen, standardmaessig 0
		 * @return string
		*/

		static function ths($count, $utf8=null, $round=null)
		{
			if(!isset($round) && isset($utf8) && !is_bool($utf8) && is_numeric($utf8))
				$round = $utf8;
			if(!isset($round))
				$round = 0;

			if(!isset($count))
				$count = 0;
			if($round === 0)
				$count = floor($count);
			elseif($round)
				$count = round($count, $round);

			$neg = false;
			if($count < 0)
			{
				$neg = true;
				$count = -$count;
			}

			$count = str_replace(array('.', ','), array(" ", ","), number_format($count, null, ',', '.'));

			if($neg)
				$count = "−".$count;

			return $count;
		}


		/**
		 * Fuegt soviele Nullen vorne an $count, dass diese mindestens $len Stellen hat.
		 * @param int $count
		 * @param int $len
		 * @return string
		*/

		static function addNulls($count, $len)
		{
			while(strlen($count) < $len)
				$count = '0'.$count;

			return $count;
		}

		/**
		 * Hilfsfunktion zum Parsen von Nachrichten. Ersetzt einen String aus Zeilenumbruechen je nach deren Anzahl durch &lt;br /&gt; oder &lt;/p&gt;(&lt;br /&gt;)*&lt;p&gt;.
		 * @param string $nls
		 * @return string
		*/

		static function message_repl_nl($nls)
		{
			$len = strlen($nls);
			if($len == 1)
				return "<br />\n\t";
			elseif($len == 2)
				return "\n</p>\n<p>\n\t";
			elseif($len > 2)
				return "\n</p>\n".str_repeat('<br />', $len-2)."\n<p>\n\t";
		}

		/**
		 * Hilfsfunktion zum Ersetzen von Links beim Parsen von Nachrichten. Haengt bei Bedarf einen Parameter mit der Session-ID an die URL in $b an.
		 * @param string $a Praefix, zum Beispiel &lt;a href=&quot;
		 * @param string $b Die URL, die ersetzt werden soll
		 * @param string $c Suffix, zum Beispiel &quot;>
		 * @param string $url_suffix URL-Suffix, zum Beispiel mit der Session-ID
		 * @return string $a.$b.$c
		*/

		static function messageReplLinks($a, $b, $c, $url_suffix="")
		{
			if(!$url_suffix)
				return $a.$b.$c;

			$url2 = html_entity_decode($b);
			if(substr($url2, 0, 7) != 'http://')
			{
				$url3 = explode('#', $url2);
				$url3[0] = explode('?', $url3[0]);
				$url = array($url3[0][0]);
				if(isset($url3[0][1]))
					$url[1] = $url3[0][1];
				else
					$url[1] = '';
				if(isset($url3[1]))
					$url[2] = $url[1];
				else
					$url[2] = '';

				if($url[1] != '')
					$url[1] .= '&';
				$url[1] .= $url_suffix;

				$url2 = $url[0].'?'.$url[1];
				if($url[2] != '')
					$url2 .= '#'.$url[2];
			}

			return $a.htmlspecialchars($url2).$c;
		}

		/**
		 * Gibt versteckte Formularfelder mit den Werten des Arrays $array aus. Nützlich, um POST-Requests zu wiederholen.
		 * @param array $array Assoziatives Array, das Parameterwert den Parameternamen zuordnet.
		 * @param int $tabs Zahl der Tabs, die vor den Code gehängt werden sollen.
		 * @param string $prefix printf-Ausdruck, mit dem die Feldnamen ausgegeben werden (zum Beispiel feld[%s], um ein Array zu übertragen). Standardmäßig %s.
		*/

		static function makeHiddenFields($array, $tabs=0, $prefix="%s")
		{
			$t = str_repeat("\t", $tabs);
			foreach($array as $k=>$v)
			{
				if(is_array($v))
				{
					self::make_hidden_fields($v, $tabs, sprintf($prefix, $k)."[%s]");
					continue;
				}
?>
<?=$t?><input type="hidden" name="<?=htmlspecialchars(sprintf($prefix, $k))?>" value="<?=preg_replace("/[\n\t\r]/e", "'&#'.ord('$0').';'", htmlspecialchars($v))?>" />
<?php
			}
		}

		/**
		 * Formatiert Planeteninformationen in ein lesbares Format.
		 * @param Planet $planet Koordinaten
		 * @param bool $show_owner Den Planeteneigentümer anzeigen?
		 * @param bool $show_alliance Die Allianz des Eigentümers anzeigen?
		 * @return string
		*/

		static function formatPlanet(Planet $planet, $show_owner=true, $show_alliance=true)
		{
			$koords = Planet::format($planet);
			$name = $planet->getName();
			if(!$name)
				return sprintf(_("%s (unbesiedelt)"), $koords);
			else
			{
				$username = $planet->getOwner();
				if(!$username || !$show_owner)
					return sprintf(_("„%s“ (%s)"), $name, $koords);
				else
				{
					if($show_alliance)
					{
						$alliance = Alliance::getUserAlliance($username);
						if($alliance)
							$username = sprintf(_("[%s] %s"), $alliance, $username);
					}
					return sprintf(_("„%s“ (%s, Eigentümer: %s)"), $name, $koords, $username);
				}
			}
		}

		/**
		 * Wie formatPlanet(), fügt aber Links auf alles ein
		 * @param Planet $planet Koordinaten
		 * @param bool $show_owner Den Planeteneigentümer anzeigen?
		 * @param bool $show_alliance Die Allianz des Eigentümers anzeigen?
		 * @return string
		 * @todo Die URLs irgendwie auslagern?
		*/

		static function formatPlanetH(Planet $planet, $show_owner=true, $show_alliance=true)
		{
			$koords = "<a href=\"".htmlspecialchars(global_setting("h_root")."/karte.php?shortcut=".urlencode($planet->__toString())."&".global_setting("URL_SUFFIX"))."\" title=\"".h(_("Diesen Planeten in der Karte anzeigen"))."\" class=\"koords\">".Planet::format($planet)."</a>";
			$name = $planet->getName();
			if(!$name)
				return sprintf(h(_("%s (unbesiedelt)")), $koords);
			else
			{
				$username = $planet->getOwner();
				if(!$username || !$show_owner)
					return sprintf(h(_("„%s“ (%s)")), $name, $koords);
				else
				{
					if($show_alliance)
					{
						$alliance = Alliance::getUserAlliance($username);
						$username = "<a href=\"".htmlspecialchars(global_setting("h_root")."/info/playerinfo.php?player=".urlencode($player)."&".global_setting("URL_SUFFIX"))."\" title=\"".h(_("Informationen zu diesem Spieler anzeigen"))."\" class=\"playername\">".htmlspecialchars($player)."</a>";
						if($alliance)
							$username = sprintf(h(_("[%s] %s")), "<a href=\"".htmlspecialchars(global_setting("h_root")."/info/allianceinfo.php?alliance=".urlencode($alliance)."&".global_setting("URL_SUFFIX"))."\" title=\"".h(_("Informationen zu dieser Allianz anzeigen"))."\" class=\"alliancename\">".htmlspecialchars($alliance)."</a>", $username);
					}
					return sprintf(h(_("„%s“ (%s, Eigentümer: %s)")), $name, $koords, $username);
				}
			}
		}

		/**
		 * Formatiert eine Fertigstellungszeit ordentlich.
		 * @param int $time
		 * @param User $user Wird benötigt, um die Zeit beim Urlaubsmodus anzuhalten
		*/

		static function formatFTime($time, $user=null)
		{
			if($user && $user->umode())
			{
				$time -= $user->getUmodeEnteringTime();
				$days = floor($time/86400);
				$time = $time%86400;
				$hours = floor($time/3600);
				$time = $time%3600;
				$minutes = floor($time/60);
				$time = $time%60;

				if($days > 0)
					return sprintf(_("%s d %02d:%02d:%02d"), F::ths($days), $hours, $minutes, $time);
				else
					return sprintf(_("%02d:%02d:%02d"), $hours, $minutes, $time);
			}

			return sprintf(_("Fertigstellung: %s"), sprintf(_("%s (Serverzeit)"), date(_("Y-m-d H:i:s"), $time)));
		}
	}