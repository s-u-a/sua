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

	import("Gui/Gui");

	class LoginGui extends Gui
	{
		protected function htmlHead()
		{
			$skins = get_skins();
			$databases = get_databases();
			$me = $this->getOption("user");
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=h(_("[LANG]"))?>">
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
		<title><?=h(_("[title_abbr_full]"))?></title>
		<script type="text/javascript">
			var url_suffix = '<?=jsentities(global_setting("URL_SUFFIX"))?>';
			var ths_utf8 = '<?=jsentities(_("[thousand_separator]"))?>';
			var h_root = '<?=jsentities(h_root)?>';
			var list_min_chars = '<?=jsentities(global_setting("LIST_MIN_CHARS"))?>';
			var res_ids = [ 'carbon', 'aluminium', 'wolfram', 'radium', 'tritium' ];
<?php
			if($me)
			{
?>
			var res_now = { 'ress' : [ <?=implode(", ", $me->getRess())?> ] };
			var slow_terminal = <?=(!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]) ? "false" : "true"?>;
			var database_id = '<?=jsentities($_SESSION["database"])?>';
			var umode = <?=$me->umode() ? "true" : "false"?>
<?php
			}
?>
		</script>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root."/login/res/javascript.js")?>"></script>
<?php
			if($me && (!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]) && $me->checkSetting('ajax'))
			{
?>
		<script type="text/javascript" src="<?=htmlspecialchars(h_root.'/sarissa.js')?>"></script>
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
				$skin_path = h_root.'/login/res/style/'.urlencode($my_skin[0]).'/style.css';

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
			<li><a href="#inner-content"<?=accesskey_attr(_("Zum Inhalt&[login/include.php|1]"))?>><?=h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
			<li><a href="#navigation"<?=accesskey_attr(_("Zur Navigation&[login/include.php|1]"))?>><?=h(_("Zur Navigation&[login/include.php|1]"))?></a></li>
			<li><a href="#time"<?=accesskey_attr(_("Zu den Spieldaten&[login/include.php|1]"))?>><?=h(_("Zu den Spieldaten&[login/include.php|1]"))?></a></li>
		</ul>
		<hr class="separator" />
<?php
			}
?>
		<div id="content-9" class="<?=htmlspecialchars($class)?>">
<?php
			if($me)
				echo format_ress($me->getRess(), 3, true, true, false, null, "inline bar", "ress", $me);
?>
			<div id="content-10" class="<?=htmlspecialchars($class)?>"><div id="content-11" class="<?=htmlspecialchars($class)?>"><div id="content-12" class="<?=htmlspecialchars($class)?>"><div id="content-13" class="<?=htmlspecialchars($class)?>">
<?php
			$locked_until = false;
			if($l = database_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="spiel error"><strong><?=h(_("Das Spiel ist derzeit gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			elseif($me && $me->userLocked())
			{
				$l = $me->lockedUntil();
				if($l) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="account error"><strong><?=h(_("Ihr Benutzeraccount ist gesperrt."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
				<hr class="separator" />
<?php
			}
			elseif($me && $me->umode())
			{
?>
				<p id="gesperrt-hinweis" class="urlaub error"><strong><?=h(_("Ihr Benutzeraccount befindet sich im Urlaubsmodus."))?></strong></p>
				<hr class="separator" />
<?php
			}
			elseif($l = fleets_locked())
			{
				if($l !== true) $locked_until = $l;
?>
				<p id="gesperrt-hinweis" class="flotten error"><strong><?=h(_("Es herrscht eine Flottensperre für feindliche Flüge."))?></strong><?php if($locked_until){?> <span id="restbauzeit-sperre"><?=h(sprintf(_("bis %s"), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i:s'), $locked_until))))?></span><?php }?></p>
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
				<h1><?php if($active_planet_0 != $active_planet){?><a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)\d+/", "\${1}".$active_planet_0, global_setting("URL_SUFFIX")))?>" title="<?=h(_("Zum vorigen Planeten wechseln&[login/include.php|1]"), false)?>"<?=accesskey_attr(_("Zum vorigen Planeten wechseln&[login/include.php|1]"))?>><?=h(_("←"))?></a> <?php }?><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), vsprintf(h(_("%d:%d:%d")), $me->getPos()))?><?php if($active_planet_1 != $active_planet){?> <a href="?<?=htmlspecialchars(preg_replace("/((^|&)planet=)\d+/", "\${1}".$active_planet_1, global_setting("URL_SUFFIX")))?>" title="<?=h(_("Zum nächsten Planeten wechseln&[login/include.php|1]"), false)?>"<?=accesskey_attr(_("Zum nächsten Planeten wechseln&[login/include.php|1]"))?>><?=h(_("→"))?></a><?php }?></h1>
<?php
			}
			else
			{
?>
				<h1><?=h(_("[title_full]"))?></h1>
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

				$cats = $me->getMessageCategoriesList();
				foreach($cats as $cat)
				{
					$message_ids = $me->getMessagesList($cat);
					foreach($message_ids as $message)
					{
						if($this->getOption("ignore_messages") && in_array($message, $this->getOption("ignore_messages"))) continue;
						$status = $me->checkMessageStatus($message, $cat);
						if($status == 1 && $cat != 8)
						{
							$ncount[$cat]++;
							$ges_ncount++;
						}
					}
				}

				if($ges_ncount > 0)
				{
					$title = array();
					$link = 'nachrichten.php';
					foreach($ncount as $type=>$count)
					{
						if($count > 0)
							$title[] = sprintf(h(_("%s: %s")), h(_("[message_".$type."]")), ths($count));
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
				<p id="neue-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root.'/login/'.$link)?>" title="<?=$title?>"<?=accesskey_attr(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount))?>><?=h(sprintf(ngettext("Sie haben %s neue &Nachricht.[login/include.php|2]", "Sie haben %s neue &Nachrichten.[login/include.php|2]", $ges_ncount), $ges_ncount))?></a></p>
<?php
				}
			}
?>
				<hr class="separator" />
				<div id="inner-content">

<!-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->

<?php
			return true;
		}

		protected function htmlFoot()
		{
			if(!$this->init_run) return false;

			$databases = get_databases();
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
			global $DISABLE_ADS;
			if((!$me || (!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"])) && (!isset($DISABLE_ADS) || !$DISABLE_ADS) && global_setting("PROTOCOL") == 'http') # Per https keine Werbung einblenden, da Google nur http unterstuetzt und dann eine Sicherheitswarnung kommt
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
			<li><a href="#ress"<?=accesskey_attr(_("Zur Rohstoffanzeige&[login/include.php|1]"))?>><?=h(_("Zur Rohstoffanzeige&[login/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
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
					<legend><?=h(_("Planeten wechseln"))?></legend>
<?php
				foreach($_GET as $key=>$val)
				{
					if($key == 'planet') continue;
?>
					<input type="hidden" name="<?=htmlspecialchars($key)?>" value="<?=htmlspecialchars($val)?>" />
<?php
				}
?>
					<select name="planet" onchange="if(this.value != <?=$me->getActivePlanet()?>) this.form.submit();" onkeyup="if(this.value != <?=$me->getActivePlanet()?>) this.form.submit();"<?=accesskey_attr(_("Ihre &Planeten[login/include.php|3]"))?> title="<?=h(_("Ihre &Planeten[login/include.php|3]"), false)?>">
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
					<noscript><div><button type="submit"><?=h(_("Wechseln"))?></button></div></noscript>
				</fieldset>
			</form>
			<hr class="separator" id="navigation-separator-1" />
			<ul id="main-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/index.php') ? ' class="active"' : ''?> id="navigation-index"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/index.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Übersicht[login/include.php|3]"))?>><?=h(_("&Übersicht[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/rohstoffe.php') ? ' class="active"' : ''?> id="navigation-rohstoffe"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/rohstoffe.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Rohstoffe[login/include.php|3]"))?>><?=h(_("&Rohstoffe[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/gebaeude.php') ? ' class="active"' : ''?> id="navigation-gebaeude"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/gebaeude.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Gebäude[login/include.php|3]"))?>><?=h(_("&Gebäude[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschung.php') ? ' class="active"' : ''?> id="navigation-forschung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/forschung.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Forschung[login/include.php|3]"))?>><?=h(_("&Forschung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/roboter.php') ? ' class="active"' : ''?> id="navigation-roboter"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/roboter.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Ro&boter[login/include.php|3]"))?>><?=h(_("Ro&boter[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/flotten.php') ? ' class="active"' : ''?> id="navigation-flotten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/flotten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("F&lotten[login/include.php|3]"))?>><?=h(_("F&lotten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/schiffswerft.php') ? ' class="active"' : ''?> id="navigation-schiffswerft"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/schiffswerft.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Schiffswerft[login/include.php|3]"))?>><?=h(_("&Schiffswerft[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verteidigung.php') ? ' class="active"' : ''?> id="navigation-verteidigung"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/verteidigung.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Verteidigung[login/include.php|3]"))?>><?=h(_("&Verteidigung[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/boerse.php') ? ' class="active"' : ''?> id="navigation-boerse"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/boerse.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Han&delsbörse[login/include.php|3]"))?>><?=h(_("Han&delsbörse[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/imperium.php') ? ' class="active"' : ''?> id="navigation-imperium"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/imperium.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("I&mperium[login/include.php|3]"))?>><?=h(_("I&mperium[login/include.php|3]"))?></a></li>
			</ul>
			<hr class="separator" id="navigation-separator-2" />
			<ul id="action-navigation">
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/karte.php') ? ' class="active"' : ''?> id="navigation-karte"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/karte.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Karte[login/include.php|3]"))?>><?=h(_("&Karte[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/allianz.php') ? ' class="active"' : ''?> id="navigation-allianz"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("All&ianz[login/include.php|3]"))?>><?=h(_("All&ianz[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/verbuendete.php') ? ' class="active"' : ''?> id="navigation-verbuendete"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/verbuendete.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("V&erbündete[login/include.php|3]"))?>><?=h(_("V&erbündete[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/highscores.php') ? ' class="active"' : ''?> id="navigation-highscores"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/highscores.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("&Highscores[login/include.php|3]"))?>><?=h(_("&Highscores[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/nachrichten.php') ? ' class="active"' : ''?> id="navigation-nachrichten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/nachrichten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Na&chrichten[login/include.php|3]"))?>><?=h(_("Na&chrichten[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/forschungsbaum.php') ? ' class="active"' : ''?> id="navigation-abhaengigkeiten"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/forschungsbaum.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Forschungsb&aum[login/include.php|3]"))?>><?=h(_("Forschungsb&aum[login/include.php|3]"))?></a></li>
				<li<?=($_SERVER['PHP_SELF'] == h_root.'/login/einstellungen.php') ? ' class="active"' : ''?> id="navigation-einstellungen"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/einstellungen.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Eins&tellungen[login/include.php|3]"))?>><?=h(_("Eins&tellungen[login/include.php|3]"))?></a></li>
<?php
				if(isset($_SESSION['admin_username']))
				{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('https://'.$_SERVER['HTTP_HOST'].h_root)?>/admin/index.php"<?=accesskey_attr(_("Adminbereich&[login/include.php|3]"))?>><?=h(_("Adminbereich&[login/include.php|3]"))?></a></li>
<?php
				}
				else
				{
?>
				<li id="navigation-abmelden"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/info/logout.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=accesskey_attr(_("Abmelden&[login/include.php|3]"))?>><?=h(_("Abmelden&[login/include.php|3]"))?></a></li>
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
				<li id="navigation-board"><a href="<?=htmlspecialchars(global_setting("USE_PROTOCOL"))?>://board.s-u-a.net/"<?=accesskey_attr(_("Board&[login/include.php|3]"))?>><?=h(_("Board&[login/include.php|3]"))?></a></li>
				<li id="navigation-faq"><a href="http://<?=htmlspecialchars(get_default_hostname().h_root)?>/faq.php"<?=accesskey_attr(_("FAQ&[login/include.php|3]"))?>><?=h(_("FAQ&[login/include.php|3]"))?></a></li>
				<li id="navigation-chat"><a href="http://<?=htmlspecialchars(get_default_hostname().h_root)?>/chat.php"<?=accesskey_attr(_("Chat&[login/include.php|3]"))?>><?=h(_("Chat&[login/include.php|3]"))?></a></li>
				<li id="navigation-developers"><a href="http://dev.s-u-a.net/"<?=accesskey_attr(_("Entwicklerseite&[login/include.php|3]"))?>><?=h(_("Entwicklerseite&[login/include.php|3]"))?></a></li>
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
			<dt><?=h(_("Serverzeit"))?></dt>
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
			dt_element.appendChild(document.createTextNode('<?=jsentities(_("Lokalzeit"))?>'));
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

			if(defined("VERSION"))
			{
?>
			<li class="version"><a href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root)?>/login/info/changelog.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Changelog anzeigen&[login/include.php|4]"), false)?>"<?=accesskey_attr(_("Changelog anzeigen&[login/include.php|4]"))?>><?=sprintf(h(_("Version %s")), htmlspecialchars(VERSION))?></a></li>
<?php
			}

			if(($rev = get_revision()) !== false)
			{
?>
			<li class="revision"><?=sprintf(h(_("Revision %s")), htmlspecialchars($rev))?></li>
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
			<li><a href="#ress"><?=h(_("Zur Rohstoffanzeige&[login/include.php|1]"))?></a></li>
			<li><a href="#inner-content"><?=h(_("Zum Inhalt&[login/include.php|1]"))?></a></li>
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
			return true;
		}

		function fatal($message)
		{
			$this->init();
?>
<p class="error"><?=$message?></p>
<?php
			$this->end();
			exit(0);
		}

		function loginFormular()
		{
			$databases = get_databases();

			$used_database = null;
			if(isset($_POST["database"]))
				$used_database = $_POST["database"];
			elseif(!$used_database && isset($_SESSION["database"]))
				$used_database = $_SESSION["database"];

			if(!$used_database && isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"] != get_default_hostname())
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
<p class="error notloggedin"><?=h((isset($_REQUEST["username"]) && isset($_REQUEST["password"])) ? _("Anmeldung fehlgeschlagen. Haben Sie sich bereits registriert und Ihren Benutzernamen und Ihr Passwort korrekt in die zugehörigen Felder beim Anmelden-Button eingetragen? Haben Sie Groß-Klein-Schreibung beim Passwort beachtet?") : _("Sie sind nicht angemeldet."))?></p>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL")."://".$_SERVER["HTTP_HOST"].$request_uri)?>" method="post" class="notloggedin">
	<fieldset>
		<dl class="form">
			<dt class="c-benutzername"><label for="i-username"><?=h(_("&Benutzername[login/include.php|5]"))?></label></dt>
			<dd class="c-benutzername"><input type="text" name="username" id="i-username" value="<?=htmlspecialchars(isset($_REQUEST["username"]) ? $_REQUEST["username"] : (isset($_SESSION["username"]) ? $_SESSION["username"] : ""))?>"<?=accesskey_attr(_("&Benutzername[login/include.php|5]"))?> /></dd>

			<dt class="c-passwort"><label for="i-password"><?=h(_("&Passwort[login/include.php|5]"))?></label></dt>
			<dd class="c-passwort"><input type="password" name="password" id="i-password"<?=accesskey_attr(_("&Passwort[login/include.php|5]"))?> /></dd>
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
				make_hidden_fields($post, 3);
?>
			<button type="submit"<?=accesskey_attr(_("&Anmelden[login/include.php|5]"))?>><?=h(_("&Anmelden[login/include.php|5]"))?></button>
		</div>
	</fieldset>
</form>
<?php
			}
			else
			{
?>
<p class="error"><?=sprintf(h(_("Nicht angemeldet. Bitte %serneut anmelden%s.")), "<a href=\"".htmlspecialchars('http://'.get_default_hostname().h_root.'/index.php')."\">", "</a>")?></p>
<?php
			}
			$this->end();
			exit(0);
		}
	}