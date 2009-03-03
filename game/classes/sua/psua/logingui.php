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
	 * @package psua
	*/

	namespace sua\psua;
	require_once dirname(dirname(dirname(dirname(__FILE__))))."/include.php";

	use \sua\Config;
	use \sua\L;
	use \sua\JS;
	use \sua\F;
	use \sua\HTTPOutput;
	use \sua\Planet;
	use \sua\Functions;
	use \sua\Message;

	/**
	 * Stellt die GUI des inneren Spielbereichs zur Verfügung.
	 * Folgende Optionen können mit Gui->setOption() gesetzt werden:
	 * - User user: Ein User-Objekt, für den die GUI dargestellt werden soll. Von diesem werden Informationen wie verfügbare Rohstoffe genommen.
	 * - bool notify: Kopfleiste über neue Nachrichten unabhängig von der Benutzereinstellung hierzu anzeigen (auf der Übersichtsseite)
	 * - array ignore_messages: Die Nachrichten-IDs in diesem Array werden nicht von der Benachrichtigung für neue Nachrichten beachtet
	 * @todo Auslagern
	*/

	class LoginGui extends \sua\Gui
	{
		/**
		 * @return void
		*/

		protected function htmlHead()
		{
			$skins = $this->getSkins();
			$USER = $this->getOption("user");
			$PLANET = $this->getOption("planet");
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
			var url_suffix = '<?=JS::jsentities($this->getOption("url_suffix"))?>';
			var ths_utf8 = '<?=JS::jsentities(_("[thousand_separator]"))?>';
			var h_root = '<?=JS::jsentities($this->getOption("h_root"))?>';
			var list_min_chars = '<?=JS::jsentities(Config::getLibConfig()->getConfigValue("list_min_chars"))?>';
			var res_ids = [ 'carbon', 'aluminium', 'wolfram', 'radium', 'tritium' ];
			var slow_terminal = <?=$this->getOption("disable_javascript") ? "true" : "false"?>;
<?php
			if($PLANET)
			{
?>
			var res_now = { 'ress' : [ <?=implode(", ", $PLANET->getRess())?> ] };
<?php
			}

			if($USER)
			{
?>
			var umode = <?=$USER->umode() ? "true" : "false"?>
<?php
			}
?>
		</script>
		<script type="text/javascript" src="<?=htmlspecialchars($this->getOption("h_root")."/res/javascript.js")?>"></script>
<?php
			if($USER && !$this->getOption("disable_javascript") && $USER->checkSetting('ajax'))
			{
?>
		<script type="text/javascript" src="<?=htmlspecialchars($this->getOption("h_root").'/software/sarissa.js')?>"></script>
<?php
			}
?>
		<script type="text/javascript">
			set_time_globals(<?=time()+1?>);
		</script>
<?php
			$skin_path = '';
			$my_skin = false;
			if($USER)
				$my_skin = $USER->checkSetting('skin');
			if(!$my_skin || !is_array($my_skin) || $my_skin[0] != "custom" && !isset($skins[$my_skin[0]]))
			{
				$my_skin = array("default", null, array());
				if($USER)
					$USER->setSetting("skin", $my_skin);
			}

			if($my_skin[0] == 'custom')
				$skin_path = $my_skin[1];
			elseif(isset($skins[$my_skin[0]]))
				$skin_path = $this->getOption("h_root").'/res/style/'.urlencode($my_skin[0]).'/style.css';

			if(trim($skin_path) != '')
			{
?>
		<link rel="stylesheet" href="<?=htmlspecialchars($skin_path)?>" type="text/css" />
<?php
			}

			$class = "";
			if($PLANET)
				$class .= ' planet-'.$PLANET->getPlanetClass();
			if((!$USER || !$USER->checkSetting("noads")) && !$this->getOption("disable_javascript") && !$this->getOption("disable_ads") && HTTPOutput::getProtocol() == 'http')
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
			if($USER)
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
			if($USER && $PLANET)
				echo F::formatRess($PLANET->getRess(), 3, true, true, false, null, "inline bar", "ress", $PLANET);
?>
			<div id="content-10" class="<?=htmlspecialchars($class)?>"><div id="content-11" class="<?=htmlspecialchars($class)?>"><div id="content-12" class="<?=htmlspecialchars($class)?>"><div id="content-13" class="<?=htmlspecialchars($class)?>">
<?php
			$locked_until = false;
			/*TODO if($l = Config::database_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="spiel error"><strong><?=L::h(_("Das Spiel ist derzeit gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=L::h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			elseif($USER && $USER->userLocked())
			{
				$l = $USER->lockedUntil();
				if($l) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="account error"><strong><?=L::h(_("Ihr Benutzeraccount ist gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=L::h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			elseif($USER && $USER->umode())
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
			}*/
			if($USER && $locked_until)
			{
?>
				<script type="text/javascript">
					init_countdown("sperre", <?=$locked_until?>, false);
				</script>
<?php
			}

			if($USER && $PLANET)
			{
				$planets = Planet::getPlanetsByUser($USER->getName());
				$cur_index = array_search($PLANET, $planets);
				if(!isset($planets[$cur_index-1]))
					$active_planet_0 = $planets[Functions::last($planets)];
				else
					$active_planet_0 = $planets[$cur_index-1];
				if(!isset($planets[$cur_index+1]))
					$active_planet_1 = $planets[Functions::first($planets)];
				else
					$active_planet_1 = $planets[$cur_index+1];
?>
				<h1><?php if($active_planet_0 != $PLANET){?><a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)[^&;]+/", "\${1}".$active_planet_0->getName(), $this->getOption("url_suffix")))?>" title="<?=L::h(_("Zum vorigen Planeten wechseln&[login/include.php|1]"), false)?>"<?=L::accesskeyAttr(_("Zum vorigen Planeten wechseln&[login/include.php|1]"))?>><?=L::h(_("←"))?></a> <?php }?><?=sprintf(L::h(_("„%s“ (%s)")), htmlspecialchars($PLANET->getGivenName()), $PLANET->format())?><?php if($active_planet_1 != $PLANET){?> <a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)[^&;]+/", "\${1}".$active_planet_1->getName(), $this->getOption("url_suffix")))?>" title="<?=L::h(_("Zum nächsten Planeten wechseln&[login/include.php|1]"), false)?>"<?=L::accesskeyAttr(_("Zum nächsten Planeten wechseln&[login/include.php|1]"))?>><?=L::h(_("→"))?></a><?php }?></h1>
<?php
			}
			else
			{
?>
				<h1><?=L::h(_("[title_full]"))?></h1>
<?php
			}

			if($USER && ($USER->checkSetting('notify') && $this->getOption("notify") !== false || $this->getOption("notify")))
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
					$status = $message_obj->messageStatus($USER->getName());
					$cat = $message_obj->messageType($USER->getName());
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
					$link .= $this->getOption("url_suffix");
?>
				<hr class="separator" />
				<p id="neue-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root").'/login/'.$link)?>" title="<?=$title?>"<?=L::accesskeyAttr(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount))?>><?=L::h(sprintf(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount), $ges_ncount))?></a></p>
<?php
				}
			}
?>
				<hr class="separator" />
				<div id="inner-content">
<?php
			if($this->getOption("error"))
			{
?>
					<p class="error"><?=htmlspecialchars($this->getOption("error"))?></p>
<?php
			}
?>

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

<?php
		}

		/**
		 * @return void
		*/

		protected function htmlFoot()
		{
			$USER = $this->getOption("user");
			$PLANET = $this->getOption("planet");
?>

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

				</div>
			</div></div>
<?php
			if($USER && $USER->checkSetting('noads'))
			{
?>
<!--[if IE]>
<?php
			}
?>
			<div id="werbung">
<?php
			if(!$this->getOption("disable_javascript") && !$this->getOption("disable_ads") && HTTPOutput::getProtocol() == 'http') # Per https keine Werbung einblenden, da Google nur http unterstuetzt und dann eine Sicherheitswarnung kommt
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
			if($USER && $USER->checkSetting('noads'))
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
			if($USER)
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
			if($USER)
			{
?>
		<div id="navigation">
			<form action="<?=htmlspecialchars($_SERVER['PHP_SELF'])?>" method="get" id="change-planet">
				<fieldset>
					<legend><?=L::h(_("Planeten wechseln"))?></legend>
<?php
				foreach($_GET+HTTPOutput::queryStringToArray($this->getOption("url_suffix"), false) as $key=>$val)
				{
					if($key == 'planet') continue;
?>
					<input type="hidden" name="<?=htmlspecialchars($key)?>" value="<?=htmlspecialchars($val)?>" />
<?php
				}
?>
					<select name="planet" onchange="if(this.value != '<?=$PLANET ? JS::jsentities($PLANET->getName()) : ""?>') this.form.submit();" onkeyup="onchange()"<?=L::accesskeyAttr(_("Ihre &Planeten[login/include.php|3]"))?> title="<?=L::h(_("Ihre &Planeten[login/include.php|3]"), false)?>">
<?php
				$planets = Planet::getPlanetsByUser($USER->getName());
				foreach($planets as $planet)
				{
?>
						<option value="<?=htmlspecialchars($planet->getName())?>"<?=($planet == $PLANET) ? ' selected="selected"' : ''?>><?=sprintf(L::h(_("„%s“ (%s)")), htmlspecialchars($planet->getName()), $planet->format())?></option>
<?php
				}
?>
					</select>
					<noscript><div><button type="submit"><?=L::h(_("Wechseln"))?></button></div></noscript>
				</fieldset>
			</form>
			<hr class="separator" id="navigation-separator-1" />
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/index.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Übersicht[login/include.php|3]"))?>><?=L::h(_("&Übersicht[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/rohstoffe.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Rohstoffe[login/include.php|3]"))?>><?=L::h(_("&Rohstoffe[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/gebaeude.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Gebäude[login/include.php|3]"))?>><?=L::h(_("&Gebäude[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/forschung.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Forschung[login/include.php|3]"))?>><?=L::h(_("&Forschung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/roboter.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("Ro&boter[login/include.php|3]"))?>><?=L::h(_("Ro&boter[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/flotten.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("F&lotten[login/include.php|3]"))?>><?=L::h(_("F&lotten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/schiffswerft.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Schiffswerft[login/include.php|3]"))?>><?=L::h(_("&Schiffswerft[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/verteidigung.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Verteidigung[login/include.php|3]"))?>><?=L::h(_("&Verteidigung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/boerse.php') ? ' class="active"' : ''?> id="navigation-boerse"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/boerse.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("Han&delsbörse[login/include.php|3]"))?>><?=L::h(_("Han&delsbörse[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/imperium.php') ? ' class="active"' : ''?> id="navigation-imperium"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/imperium.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("I&mperium[login/include.php|3]"))?>><?=L::h(_("I&mperium[login/include.php|3]"))?></a></li>
			</ul>
			<hr class="separator" id="navigation-separator-2" />
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/karte.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Karte[login/include.php|3]"))?>><?=L::h(_("&Karte[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/allianz.php') ? ' class="active"' : ''?> id="navigation-allianz"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/allianz.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("All&ianz[login/include.php|3]"))?>><?=L::h(_("All&ianz[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/verbuendete.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("V&erbündete[login/include.php|3]"))?>><?=L::h(_("V&erbündete[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/highscores.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("&Highscores[login/include.php|3]"))?>><?=L::h(_("&Highscores[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/nachrichten.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("Na&chrichten[login/include.php|3]"))?>><?=L::h(_("Na&chrichten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/forschungsbaum.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/forschungsbaum.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("Forschungsb&aum[login/include.php|3]"))?>><?=L::h(_("Forschungsb&aum[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == $this->getOption("h_root").'/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/einstellungen.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("Eins&tellungen[login/include.php|3]"))?>><?=L::h(_("Eins&tellungen[login/include.php|3]"))?></a></li>
<?php
				if(isset($_SESSION['admin_username']))
				{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('https://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/admin/index.php"<?=L::accesskeyAttr(_("Adminbereich&[login/include.php|3]"))?>><?=L::h(_("Adminbereich&[login/include.php|3]"))?></a></li>
<?php
				}
				else
				{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/info/logout.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>"<?=L::accesskeyAttr(_("Abmelden&[login/include.php|3]"))?>><?=L::h(_("Abmelden&[login/include.php|3]"))?></a></li>
<?php
				}
?>
			</ul>
<?php
				if($USER->checkSetting('show_extern'))
				{
?>
			<hr class="separator" id="navigation-separator-3" />
			<ul id="external-navigation">
				<li id="navigation-board"><a href="<?=htmlspecialchars($this->getOption("protocol"))?>://board.s-u-a.net/"<?=L::accesskeyAttr(_("Board&[login/include.php|3]"))?>><?=L::h(_("Board&[login/include.php|3]"))?></a></li>
				<li id="navigation-faq"><a href="http://<?=htmlspecialchars(Config::get_default_hostname().$this->getOption("h_root"))?>/faq.php"<?=L::accesskeyAttr(_("FAQ&[login/include.php|3]"))?>><?=L::h(_("FAQ&[login/include.php|3]"))?></a></li>
				<li id="navigation-chat"><a href="http://<?=htmlspecialchars(Config::get_default_hostname().$this->getOption("h_root"))?>/chat.php"<?=L::accesskeyAttr(_("Chat&[login/include.php|3]"))?>><?=L::h(_("Chat&[login/include.php|3]"))?></a></li>
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
			if(!$USER || $this->getOption("disable_javascript"))
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
			if($USER)
			{
?>
			<li class="username"><?=htmlspecialchars($USER->getName())?></li>
<?php
			}
?>
			<li class="version"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$this->getOption("h_root"))?>/info/changelog.php?<?=htmlspecialchars($this->getOption("url_suffix"))?>" title="<?=L::h(_("Changelog anzeigen&[login/include.php|4]"), false)?>"<?=L::accesskeyAttr(_("Changelog anzeigen&[login/include.php|4]"))?>><?=sprintf(L::h(_("Version %s")), htmlspecialchars(\sua\VERSION))?></a></li>
		</ul>

<?php
			if($USER)
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
			if($USER && !$this->getOption("disable_javascript"))
			{
				if($USER->checkSetting('tooltips') || $USER->checkSetting('shortcuts') || $USER->checkSetting('ress_refresh') > 0)
				{
?>
		<script type="text/javascript">
<?php
					if($USER->checkSetting('shortcuts'))
					{
?>
			get_key_elements();
<?php
					}
					if($USER->checkSetting('tooltips'))
					{
?>
			load_titles();
<?php
					}
					if($USER->checkSetting('ress_refresh') > 0)
					{
						$ress = $USER->getRess();
						$prod = $USER->getProduction();
						$limit = $USER->getProductionLimit();
?>
			refresh_ress(<?=$USER->checkSetting('ress_refresh')*1000?>, 'ress', [ <?=$ress[0]?>, <?=$ress[1]?>, <?=$ress[2]?>, <?=$ress[3]?>, <?=$ress[4]?> ], [ <?=$prod[0]?>, <?=$prod[1]?>, <?=$prod[2]?>, <?=$prod[3]?>, <?=$prod[4]?>] , [ <?=$limit[0]?>, <?=$limit[1]?>, <?=$limit[2]?>, <?=$limit[3]?>, <?=$limit[4]?> ]);
<?php
					}
?>
		</script>
<?php
				}

				if($this->getOption("protocol") == "https")
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
		 * Eine Fehlermeldung kann per GUI::setOption("login_error") festgelegt werden.
		 * @return void
		*/

		function loginFormular()
		{
			try
			{
				$this->init();
			}
			catch(\sua\GuiException $e)
			{
			}

			$query_string = preg_replace("/(&|^)(username|password)=[^&]*/", "", $_SERVER["QUERY_STRING"]);
			if(strlen($query_string) > 0) $query_string = "?".$query_string;
			$request_uri = preg_replace("/\\?.*\$/", $query_string, $_SERVER["REQUEST_URI"]);
?>
<form action="<?=htmlspecialchars($this->getOption("protocol")."://".$_SERVER["HTTP_HOST"].$this->getOption("h_root")."/login.php?".$this->getOption("url_suffix"))?>" method="post" class="notloggedin">
	<fieldset>
		<dl class="form">
			<dt class="c-benutzername"><label for="i-username"><?=L::h(_("&Benutzername[login/include.php|5]"))?></label></dt>
			<dd class="c-benutzername"><input type="text" name="username" id="i-username" value="<?=htmlspecialchars($this->getOption("login_username"))?>"<?=L::accesskeyAttr(_("&Benutzername[login/include.php|5]"))?> /></dd>

			<dt class="c-passwort"><label for="i-password"><?=L::h(_("&Passwort[login/include.php|5]"))?></label></dt>
			<dd class="c-passwort"><input type="password" name="password" id="i-password"<?=L::accesskeyAttr(_("&Passwort[login/include.php|5]"))?> /></dd>
		</dl>
		<div class="button">
<?php
			if($this->getOption("login_resume"))
			{
?>
			<input type="hidden" name="resume" value="<?=htmlspecialchars($this->getOption("login_resume"))?>" />
<?php
			}

			if($this->getOption("login_keep_post"))
			{
?>
			<input type="hidden" name="keep_post" value="on" />
<?php
				$post = $_POST;
				foreach(array("username", "password") as $k)
				{
					if(isset($post[$k])) unset($post[$k]);
				}
				F::makeHiddenFields($post, 3);
			}
?>
			<button type="submit"<?=L::accesskeyAttr(_("&Anmelden[login/include.php|5]"))?>><?=L::h(_("&Anmelden[login/include.php|5]"))?></button>
		</div>
	</fieldset>
</form>
<?php
			$this->end();
			exit(0);
		}

		/**
		 * Sucht nach installierten Skins und liefert ein Array des folgenden
		 * Formats zurueck:
		 * ( ID => [ Name, ( Einstellungsname => ( moeglicher Wert ) ) ] )
		 * @return array
		 * @todo
		*/

		function getSkins()
		{
			# Vorgegebene Skins-Liste bekommen
			$sroot = $this->getOption("s_root");
			$skins = array();
			if(is_dir($sroot."/res/style"))
			{
				$dh = opendir($sroot."/res/style");
				while(($fname = readdir($dh)) !== false)
				{
					if($fname[0] == ".") continue;
					$path = $sroot."/res/style/".$fname;
					if(!is_dir($path) || !is_readable($path)) continue;
					if(!is_file($path."/types") || !is_readable($path."/types")) continue;
					$skins_file = preg_split("/\r\n|\r|\n/", file_get_contents($path."/types"));
					$new_skin = &$skins[$fname];
					$new_skin = array(array_shift($skins_file), array());
					foreach($skins_file as $skins_line)
					{
						$skins_line = explode("\t", $skins_line);
						if(count($skins_line) < 2)
							continue;
						$new_skin[1][array_shift($skins_line)] = $skins_line;
					}
					unset($new_skin);
				}
				closedir($dh);
			}
			return $skins;
		}
	}