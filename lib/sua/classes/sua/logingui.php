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
	 * Stellt die GUI des inneren Spielbereichs zur Verfügung.
	 * Folgende Optionen können mit Gui->setOption() gesetzt werden:
	 * - User user: Ein User-Objekt, für den die GUI dargestellt werden soll. Von diesem werden Informationen wie verfügbare Rohstoffe genommen.
	 * - bool notify: Kopfleiste über neue Nachrichten unabhängig von der Benutzereinstellung hierzu anzeigen (auf der Übersichtsseite)
	 * - array ignore_messages: Die Nachrichten-IDs in diesem Array werden nicht von der Benachrichtigung für neue Nachrichten beachtet
	 * @todo Auslagern
	*/

	class LoginGui extends Gui
	{
		/**
		 * @return void
		*/

		protected function htmlHead()
		{
			$skins = Config::getSkins();
			$databases = Config::get_databases();
			$me = $this->getOption("user");
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=L::h(_("[LANG]"))?>">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
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
		<title><?=L::h(_("[title_abbr_full]"))?></title>
		<script type="text/javascript">
			var url_suffix = '<?=JS::jsentities(global_setting("URL_SUFFIX"))?>';
			var ths_utf8 = '<?=JS::jsentities(_("[thousand_separator]"))?>';
			var h_root = '<?=JS::jsentities(global_setting("h_root"))?>';
			var list_min_chars = '<?=JS::jsentities(Config::getLibConfig()->getConfigValue("list_min_chars"))?>';
			var res_ids = [ 'carbon', 'aluminium', 'wolfram', 'radium', 'tritium' ];
<?php
			if($me)
			{
?>
			var res_now = { 'ress' : [ <?=implode(", ", $me->getRess())?> ] };
			var slow_terminal = <?=(!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]) ? "false" : "true"?>;
			var database_id = '<?=JS::jsentities($_SESSION["database"])?>';
			var umode = <?=$me->umode() ? "true" : "false"?>
<?php
			}
?>
		</script>
		<script type="text/javascript" src="<?=htmlspecialchars(global_setting("h_root")."/login/res/javascript.js")?>"></script>
<?php
			if($me && (!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]) && $me->checkSetting('ajax'))
			{
?>
		<script type="text/javascript" src="<?=htmlspecialchars(global_setting("h_root").'/software/sarissa.js')?>"></script>
<?php
			}
?>
		<script type="text/javascript">
			set_time_globals(<?=time()+1?>);
		</script>
<?php
			$skin_path = '';
			$my_skin = false;
			if($me)
				$my_skin = $me->checkSetting('skin');
			if(!$my_skin || !is_array($my_skin) || $my_skin[0] != "custom" && !isset($skins[$my_skin[0]]))
			{
				$my_skin = array("default", null, array());
				if($me)
					$me->setSetting("skin", $my_skin);
			}

			if($my_skin[0] == 'custom')
				$skin_path = $my_skin[1];
			elseif(isset($skins[$my_skin[0]]))
				$skin_path = global_setting("h_root").'/login/res/style/'.urlencode($my_skin[0]).'/style.css';

			if(trim($skin_path) != '')
			{
?>
		<link rel="stylesheet" href="<?=htmlspecialchars($skin_path)?>" type="text/css" />
<?php
			}

			$class = "";
			if($me)
				$class .= ' planet-'.$me->getPlanetClass();
			if(!$me || !$me->checkSetting('noads'))
				$class .= ' mit-werbung';
			else
				$class .= ' ohne-werbung';

			if($my_skin[0] == "custom" && isset($my_skin[2]) && !is_array($my_skin[2]))
				$class .= preg_replace("/^|\s+/", " skin-", $my_skin[2]);
			elseif($my_skin[0] != "custom")
			{
				$i = 0;
				foreach($skins[$my_skin[0]][1] as $vs)
				{
					if(isset($my_skin[2]) && is_array($my_skin[2]) && isset($my_skin[2][$i]) && isset($vs[$my_skin[2][$i]]))
						$v = $my_skin[2][$i];
					else
						$v = 0;
					$class .= " skin-".$i."-".$v;
					$i++;
				}
			}
			$class = trim($class);
?>
	</head>
	<body class="<?=htmlspecialchars($class)?>" id="body-root"><div id="content-1" class="<?=htmlspecialchars($class)?>"><div id="content-2" class="<?=htmlspecialchars($class)?>"><div id="content-3" class="<?=htmlspecialchars($class)?>"><div id="content-4" class="<?=htmlspecialchars($class)?>"><div id="content-5" class="<?=htmlspecialchars($class)?>"><div id="content-6" class="<?=htmlspecialchars($class)?>"><div id="content-7" class="<?=htmlspecialchars($class)?>"><div id="content-8" class="<?=htmlspecialchars($class)?>">
<?php
			if($me)
			{
?>
		<ul id="links-down" class="cross-navigation">
			<li><a href="#inner-content"<?=L::accesskeyAttr(_("Zum Inhalt&[login/include.php|1]"))?>><?=L::h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
			<li><a href="#navigation"<?=L::accesskeyAttr(_("Zur Navigation&[login/include.php|1]"))?>><?=L::h(_("Zur Navigation&[login/include.php|1]"))?></a></li>
			<li><a href="#time"<?=L::accesskeyAttr(_("Zu den Spieldaten&[login/include.php|1]"))?>><?=L::h(_("Zu den Spieldaten&[login/include.php|1]"))?></a></li>
		</ul>
		<hr class="separator" />
<?php
			}
?>
		<div id="content-9" class="<?=htmlspecialchars($class)?>">
<?php
			if($me)
				echo F::formatRess($me->getRess(), 3, true, true, false, null, "inline bar", "ress", $me);
?>
			<div id="content-10" class="<?=htmlspecialchars($class)?>"><div id="content-11" class="<?=htmlspecialchars($class)?>"><div id="content-12" class="<?=htmlspecialchars($class)?>"><div id="content-13" class="<?=htmlspecialchars($class)?>">
<?php
			$locked_until = false;
			if($l = Config::database_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="spiel error"><strong><?=L::h(_("Das Spiel ist derzeit gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=L::h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			elseif($me && $me->userLocked())
			{
				$l = $me->lockedUntil();
				if($l) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="account error"><strong><?=L::h(_("Ihr Benutzeraccount ist gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=L::h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			elseif($me && $me->umode())
			{
?>
				<p id="gesperrt-hinweis" class="urlaub error"><strong><?=L::h(_("Ihr Benutzeraccount befindet sich im Urlaubsmodus."))?></strong></p>
				<hr class="separator" />
<?php
			}
			elseif($l = Config::fleets_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="flotten error"><strong><?=L::h(_("Es herrscht eine Flottensperre für feindliche Flüge."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=L::h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			if($me && $locked_until)
			{
?>
				<script type="text/javascript">
					init_countdown("sperre", <?=$locked_until?>, false);
				</script>
<?php
			}

			if($me)
			{
				$active_planet = $me->getActivePlanet();
				$active_planet_0 = $active_planet-1;
				if($active_planet_0 < 0) $active_planet_0 = max($me->getPlanetsList());
				$active_planet_1 = $active_planet+1;
				if($active_planet_1 > max($me->getPlanetsList())) $active_planet_1 = 0;
?>
				<h1><?php if($active_planet_0 != $active_planet){?><a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)\d+/", "\${1}".$active_planet_0, global_setting("URL_SUFFIX")))?>" title="<?=L::h(_("Zum vorigen Planeten wechseln&[login/include.php|1]"), false)?>"<?=L::accesskeyAttr(_("Zum vorigen Planeten wechseln&[login/include.php|1]"))?>><?=L::h(_("←"))?></a> <?php }?><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?><?php if($active_planet_1 != $active_planet){?> <a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)\d+/", "\${1}".$active_planet_1, global_setting("URL_SUFFIX")))?>" title="<?=L::h(_("Zum nächsten Planeten wechseln&[login/include.php|1]"), false)?>"<?=L::accesskeyAttr(_("Zum nächsten Planeten wechseln&[login/include.php|1]"))?>><?=L::h(_("→"))?></a><?php }?></h1>
<?php
			}
			else
			{
?>
				<h1><?=L::h(_("[title_full]"))?></h1>
<?php
			}

			if($me && ($me->checkSetting('notify') && $this->getOption("notify") !== false || $this->getOption("notify")))
			{
				$ncount = array(
					1 => 0,
					2 => 0,
					3 => 0,
					4 => 0,
					5 => 0,
					6 => 0,
					7 => 0
				);
				$ges_ncount = 0;

				$message_ids = Message::getUserMessages($user);
				foreach($message_ids as $message)
				{
					if($this->getOption("ignore_messages") && in_array($message, $this->getOption("ignore_messages")))
						continue;
					$message_obj = Classes::Message($message);
					$status = $message_obj->messageStatus($me->getName());
					$cat = $message_obj->messageType($me->getName());
					if($status == Message::STATUS_NEU && $cat != Message::TYPE)
					{
						$ncount[$cat]++;
						$ges_ncount++;
					}
				}

				if($ges_ncount > 0)
				{
					$title = array();
					$link = 'nachrichten.php';
					foreach($ncount as $type=>$count)
					{
						if($count > 0)
							$title[] = sprintf(h(_("%s: %s")), h(_("[message_".$type."]")), F::ths($count));
						if($count == $ges_ncount)
							$link .= '?type='.urlencode($type);
					}
					$title = implode('; ', $title);
					if(strpos($link, '?') === false)
						$link .= '?';
					else
						$link .= '&';
					$link .= global_setting("URL_SUFFIX");
?>
				<hr class="separator" />
				<p id="neue-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root").'/login/'.$link)?>" title="<?=$title?>"<?=L::accesskeyAttr(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount))?>><?=L::h(sprintf(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount), $ges_ncount))?></a></p>
<?php
				}
			}
?>
				<hr class="separator" />
				<div id="inner-content">

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

<?php
		}

		/**
		 * @return void
		*/

		protected function htmlFoot()
		{
			$databases = Config::get_databases();
			$me = $this->getOption("user");
?>

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

				</div>
			</div></div>
<?php
			if($me && $me->checkSetting('noads'))
			{
?>
<!--[if IE]>
<?php
			}
?>
			<div id="werbung">
<?php
			if((!$me || (!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"])) && !$this->getOption("disable_ads") && HTTPOutput::getProtocol() == 'http') # Per https keine Werbung einblenden, da Google nur http unterstuetzt und dann eine Sicherheitswarnung kommt
			{
?>
				<div class="google-params" id="google-color-border-red"></div>
				<div class="google-params" id="google-color-border-green"></div>
				<div class="google-params" id="google-color-border-blue"></div>
				<div class="google-params" id="google-color-text-red"></div>
				<div class="google-params" id="google-color-text-green"></div>
				<div class="google-params" id="google-color-text-blue"></div>
				<div class="google-params" id="google-color-bg-red"></div>
				<div class="google-params" id="google-color-bg-green"></div>
				<div class="google-params" id="google-color-bg-blue"></div>
				<div class="google-params" id="google-color-link-red"></div>
				<div class="google-params" id="google-color-link-green"></div>
				<div class="google-params" id="google-color-link-blue"></div>
				<div class="google-params" id="google-color-url-red"></div>
				<div class="google-params" id="google-color-url-green"></div>
				<div class="google-params" id="google-color-url-blue"></div>
				<script type="text/javascript">
				// <![CDATA[
					var mkcolourpart_digits = [ "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f" ];
					function mkcolourpart(a_value)
					{
						if(a_value > 255) a_value = 255;
						if(a_value < 0) a_value = 0;
						var digit2 = a_value%16;
						var digit1 = (a_value-digit2)/16;
						return mkcolourpart_digits[digit1]+mkcolourpart_digits[digit2];
					}

					function mkcolour(a_type)
					{
						var v_red = document.getElementById("google-color-"+a_type+"-red").offsetHeight;
						var v_green = document.getElementById("google-color-"+a_type+"-green").offsetHeight;
						var v_blue = document.getElementById("google-color-"+a_type+"-blue").offsetHeight;
						if(!v_red || !v_green || !v_blue) return undefined;
						return mkcolourpart(v_red-1)+mkcolourpart(v_green-1)+mkcolourpart(v_blue-1);
					}

					google_ad_client = "pub-2662652449578921";
					google_ad_slot = "2761845030";
					google_ad_width = 120;
					google_ad_height = 600;
					var g_vars = [ "border", "text", "bg", "link", "url" ];
					for(var i=0; i<g_vars.length; i++)
						window["google_color_"+g_vars[i]] = mkcolour(g_vars[i]);
				// ]]>
				</script>
				<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
<?php
			}
?>
			</div>
<?php
			if($me && $me->checkSetting('noads'))
			{
?>
<![endif]-->
<?php
			}
?>
			<div id="css-1"></div>
		</div></div>
		<div id="css-2"></div>
		</div>

<?php
			if($me)
			{
?>
		<hr class="separator" />

		<ul id="links-up-1" class="cross-navigation">
			<li><a href="#ress"<?=L::accesskeyAttr(_("Zur Rohstoffanzeige&[login/include.php|1]"))?>><?=L::h(_("Zur Rohstoffanzeige&[login/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=L::h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
		</ul>
<?php
			}
?>

		<hr class="separator" />

<?php
			if($me)
			{
?>
		<div id="navigation">
			<form action="<?=htmlspecialchars($_SERVER['PHP_SELF'])?>" method="get" id="change-planet">
				<fieldset>
					<legend><?=L::h(_("Planeten wechseln"))?></legend>
<?php
				foreach($_GET as $key=>$val)
				{
					if($key == 'planet') continue;
?>
					<input type="hidden" name="<?=htmlspecialchars($key)?>" value="<?=htmlspecialchars($val)?>" />
<?php
				}
?>
					<select name="planet" onchange="if(this.value != <?=$me->getActivePlanet()?>) this.form.submit();" onkeyup="if(this.value != <?=$me->getActivePlanet()?>) this.form.submit();"<?=L::accesskeyAttr(_("Ihre &Planeten[login/include.php|3]"))?> title="<?=L::h(_("Ihre &Planeten[login/include.php|3]"), false)?>">
<?php
				$active_planet = $me->getActivePlanet();
				$planets = $me->getPlanetsList();
				foreach($planets as $planet)
				{
					$me->setActivePlanet($planet);
?>
						<option value="<?=htmlspecialchars($planet)?>"<?=($planet == $active_planet) ? ' selected="selected"' : ''?>><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?></option>
<?php
				}
				$me->setActivePlanet($active_planet);
?>
					</select>
					<noscript><div><button type="submit"><?=L::h(_("Wechseln"))?></button></div></noscript>
				</fieldset>
			</form>
			<hr class="separator" id="navigation-separator-1" />
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/index.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Übersicht[login/include.php|3]"))?>><?=L::h(_("&Übersicht[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/rohstoffe.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Rohstoffe[login/include.php|3]"))?>><?=L::h(_("&Rohstoffe[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/gebaeude.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Gebäude[login/include.php|3]"))?>><?=L::h(_("&Gebäude[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/forschung.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Forschung[login/include.php|3]"))?>><?=L::h(_("&Forschung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/roboter.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Ro&boter[login/include.php|3]"))?>><?=L::h(_("Ro&boter[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/flotten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("F&lotten[login/include.php|3]"))?>><?=L::h(_("F&lotten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/schiffswerft.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Schiffswerft[login/include.php|3]"))?>><?=L::h(_("&Schiffswerft[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/verteidigung.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Verteidigung[login/include.php|3]"))?>><?=L::h(_("&Verteidigung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/boerse.php') ? ' class="active"' : ''?> id="navigation-boerse"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/boerse.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Han&delsbörse[login/include.php|3]"))?>><?=L::h(_("Han&delsbörse[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/imperium.php') ? ' class="active"' : ''?> id="navigation-imperium"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("I&mperium[login/include.php|3]"))?>><?=L::h(_("I&mperium[login/include.php|3]"))?></a></li>
			</ul>
			<hr class="separator" id="navigation-separator-2" />
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/karte.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Karte[login/include.php|3]"))?>><?=L::h(_("&Karte[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/allianz.php') ? ' class="active"' : ''?> id="navigation-allianz"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("All&ianz[login/include.php|3]"))?>><?=L::h(_("All&ianz[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/verbuendete.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("V&erbündete[login/include.php|3]"))?>><?=L::h(_("V&erbündete[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/highscores.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Highscores[login/include.php|3]"))?>><?=L::h(_("&Highscores[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/nachrichten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Na&chrichten[login/include.php|3]"))?>><?=L::h(_("Na&chrichten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/forschungsbaum.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/forschungsbaum.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Forschungsb&aum[login/include.php|3]"))?>><?=L::h(_("Forschungsb&aum[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == global_setting("h_root").'/login/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/einstellungen.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Eins&tellungen[login/include.php|3]"))?>><?=L::h(_("Eins&tellungen[login/include.php|3]"))?></a></li>
<?php
				if(isset($_SESSION['admin_username']))
				{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('https://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/admin/index.php"<?=L::accesskeyAttr(_("Adminbereich&[login/include.php|3]"))?>><?=L::h(_("Adminbereich&[login/include.php|3]"))?></a></li>
<?php
				}
				else
				{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/info/logout.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Abmelden&[login/include.php|3]"))?>><?=L::h(_("Abmelden&[login/include.php|3]"))?></a></li>
<?php
				}
?>
			</ul>
<?php
				if($me->checkSetting('show_extern'))
				{
?>
			<hr class="separator" id="navigation-separator-3" />
			<ul id="external-navigation">
				<li id="navigation-board"><a href="<?=htmlspecialchars(global_setting("USE_PROTOCOL"))?>://board.s-u-a.net/"<?=L::accesskeyAttr(_("Board&[login/include.php|3]"))?>><?=L::h(_("Board&[login/include.php|3]"))?></a></li>
				<li id="navigation-faq"><a href="http://<?=htmlspecialchars(Config::get_default_hostname().global_setting("h_root"))?>/faq.php"<?=L::accesskeyAttr(_("FAQ&[login/include.php|3]"))?>><?=L::h(_("FAQ&[login/include.php|3]"))?></a></li>
				<li id="navigation-chat"><a href="http://<?=htmlspecialchars(Config::get_default_hostname().global_setting("h_root"))?>/chat.php"<?=L::accesskeyAttr(_("Chat&[login/include.php|3]"))?>><?=L::h(_("Chat&[login/include.php|3]"))?></a></li>
				<li id="navigation-developers"><a href="http://dev.s-u-a.net/"<?=L::accesskeyAttr(_("Entwicklerseite&[login/include.php|3]"))?>><?=L::h(_("Entwicklerseite&[login/include.php|3]"))?></a></li>
			</ul>
<?php
				}
?>
		</div>
<?php
			}
?>

		<hr class="separator" />

		<dl class="inline bar" id="time">
			<dt><?=L::h(_("Serverzeit"))?></dt>
			<dd id="time-server"><?=date(_("H:i:s"), time()+1)?></dd>
		</dl>
<?php
			if(!$me || (!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]))
			{
?>
		<script type="text/javascript">
			var dd_element = document.createElement('dd');
			dd_element.setAttribute('id', 'time-local');
			dd_element.appendChild(document.createTextNode(<?=_("mk2(local_time_obj.getHours())+':'+mk2(local_time_obj.getMinutes())+':'+mk2(local_time_obj.getSeconds())")?>));
			var dt_element = document.createElement('dt');
			dt_element.appendChild(document.createTextNode('<?=JS::jsentities(_("Lokalzeit"))?>'));
			var time_element = document.getElementById('time');
			time_element.insertBefore(dd_element, time_element.firstChild);
			time_element.insertBefore(dt_element, dd_element);
			setInterval('time_up()', 1000);
		</script>
<?php
			}
?>

		<hr class="separator" />

		<ul id="gameinfo">
<?php
			if($me)
			{
?>
			<li class="username"><?=htmlspecialchars($me->getName())?></li>
<?php
			}

			if(isset($_SESSION["database"]))
				$database = $_SESSION["database"];
			elseif(isset($_REQUEST["database"]))
				$database = $_REQUEST["database"];
			else
				$database = global_setting("DB");
			if($database)
			{
?>
			<li class="database"><?=htmlspecialchars($databases[$database]['name'])?></li>
<?php
			}

			if(global_setting("VERSION"))
			{
?>
			<li class="version"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].global_setting("h_root"))?>/login/info/changelog.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Changelog anzeigen&[login/include.php|4]"), false)?>"<?=L::accesskeyAttr(_("Changelog anzeigen&[login/include.php|4]"))?>><?=sprintf(h(_("Version %s")), htmlspecialchars(global_setting("VERSION")))?></a></li>
<?php
			}
?>
		</ul>

<?php
			if($me)
			{
?>
		<hr class="separator" />

		<ul id="links-up-2" class="cross-navigation">
			<li><a href="#ress"><?=L::h(_("Zur Rohstoffanzeige&[login/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=L::h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
		</ul>
<?php
			}
?>

		<div id="css-3"></div>
		</div></div></div></div></div></div></div></div>
		<div id="css-4"></div>
<?php
			if($me && (!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]))
			{
				if($me->checkSetting('tooltips') || $me->checkSetting('shortcuts') || $me->checkSetting('ress_refresh') > 0)
				{
?>
		<script type="text/javascript">
<?php
					if($me->checkSetting('shortcuts'))
					{
?>
			get_key_elements();
<?php
					}
					if($me->checkSetting('tooltips'))
					{
?>
			load_titles();
<?php
					}
					if($me->checkSetting('ress_refresh') > 0)
					{
						$ress = $me->getRess();
						$prod = $me->getProduction();
						$limit = $me->getProductionLimit();
?>
			refresh_ress(<?=$me->checkSetting('ress_refresh')*1000?>, 'ress', [ <?=$ress[0]?>, <?=$ress[1]?>, <?=$ress[2]?>, <?=$ress[3]?>, <?=$ress[4]?> ], [ <?=$prod[0]?>, <?=$prod[1]?>, <?=$prod[2]?>, <?=$prod[3]?>, <?=$prod[4]?>] , [ <?=$limit[0]?>, <?=$limit[1]?>, <?=$limit[2]?>, <?=$limit[3]?>, <?=$limit[4]?> ]);
<?php
					}
?>
		</script>
<?php
				}

				if(global_setting("PROTOCOL") == "https")
					$analytics_prefix = "https://ssl";
				else
					$analytics_prefix = "http://www";
?>
		<script src="<?=htmlspecialchars($analytics_prefix)?>.google-analytics.com/ga.js" type="text/javascript"></script>
		<script type="text/javascript">
			if(typeof _gat != "undefined")
			{
				var pageTracker = _gat._getTracker("UA-471643-1");
				pageTracker._initData();
				pageTracker._trackPageview();
			}
		</script>
<?php
			}
?>
	</body>
</html>
<?php
		}

		/**
		 * Zeigt einen fatalen Fehler an und beendet das Script. Es wird nur der
		 * Fehler angezeigt, sonst nichts.
		 * @param string $message
		 * @return void
		*/

		function fatal($message)
		{
			$this->init();
?>
<p class="error"><?=$message?></p>
<?php
			$this->end();
			exit(0);
		}

		/**
		 * Zeigt statt der Seite ein vollständiges Login-Formular in der LoginGui
		 * an. Beendet das Script.
		 * @return void
		*/

		function loginFormular()
		{
			$databases = Config::get_databases();

			$used_database = null;
			if(isset($_POST["database"]))
				$used_database = $_POST["database"];
			elseif(!$used_database && isset($_SESSION["database"]))
				$used_database = $_SESSION["database"];

			if(!$used_database && isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"] != Config::get_default_hostname())
			{
				$used_databases = 0;
				foreach($databases as $id=>$info)
				{
					if($info["hostname"] == $_SERVER["HTTP_HOST"])
					{
						if(++$used_databases > 1)
							break;
						$used_database = $id;
					}
				}
				if($used_databases != 1)
					$used_database = null;
			}

			$this->init();

			if($used_database)
			{
				$query_string = preg_replace("/(&|^)(username|password|database|dontresume)=[^&]*/", "", $_SERVER["QUERY_STRING"]);
				if(strlen($query_string) > 0) $query_string = "?".$query_string;
				$request_uri = preg_replace("/\\?.*/", $query_string, $_SERVER["REQUEST_URI"]);
?>
<p class="error notloggedin"><?=L::h((isset($_REQUEST["username"]) && isset($_REQUEST["password"])) ? _("Anmeldung fehlgeschlagen. Haben Sie sich bereits registriert und Ihren Benutzernamen und Ihr Passwort korrekt in die zugehörigen Felder beim Anmelden-Button eingetragen? Haben Sie Groß-Klein-Schreibung beim Passwort beachtet?") : _("Sie sind nicht angemeldet."))?></p>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL")."://".$_SERVER["HTTP_HOST"].$request_uri)?>" method="post" class="notloggedin">
	<fieldset>
		<dl class="form">
			<dt class="c-benutzername"><label for="i-username"><?=L::h(_("&Benutzername[login/include.php|5]"))?></label></dt>
			<dd class="c-benutzername"><input type="text" name="username" id="i-username" value="<?=htmlspecialchars(isset($_REQUEST["username"]) ? $_REQUEST["username"] : (isset($_SESSION["username"]) ? $_SESSION["username"] : ""))?>"<?=L::accesskeyAttr(_("&Benutzername[login/include.php|5]"))?> /></dd>

			<dt class="c-passwort"><label for="i-password"><?=L::h(_("&Passwort[login/include.php|5]"))?></label></dt>
			<dd class="c-passwort"><input type="password" name="password" id="i-password"<?=L::accesskeyAttr(_("&Passwort[login/include.php|5]"))?> /></dd>
		</dl>
		<div class="button">
			<input type="hidden" name="database" value="<?=htmlspecialchars($used_database)?>" />
			<input type="hidden" name="dontresume" value="on" />
<?php
				$post = $_POST;
				foreach(array("username", "password", "database", "dontresume") as $k)
				{
					if(isset($post[$k])) unset($post[$k]);
				}
				F::makeHiddenFields($post, 3);
?>
			<button type="submit"<?=L::accesskeyAttr(_("&Anmelden[login/include.php|5]"))?>><?=L::h(_("&Anmelden[login/include.php|5]"))?></button>
		</div>
	</fieldset>
</form>
<?php
			}
			else
			{
?>
<p class="error"><?=sprintf(h(_("Nicht angemeldet. Bitte %serneut anmelden%s.")), "<a href=\"".htmlspecialchars('http://'.Config::get_default_hostname().global_setting("h_root").'/index.php')."\">", "</a>")?></p>
<?php
			}
			$this->end();
			exit(0);
		}
	}