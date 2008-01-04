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
	require('include.php');

	$channels = array(
		'sua' => array(_('S-U-A player’s channel'), 'irc.gamesurge.net', '#sua'),
		'sua-dev' => array(_('S-U-A developer’s channel'), 'irc.epd-me.net', '#sua-dev')
	);

	$popup = (isset($_REQUEST['channel']) && isset($_REQUEST['nickname']) && isset($channels[$_REQUEST['channel']]) && isset($_GET['popup']) && $_GET['popup']);

	if(!$popup)
	{
		home_gui::html_head('http://'.$_SERVER['HTTP_HOST'].h_root.'/chat/');
?>
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Chat")))?></h2>
<?php
	}

	if(!isset($_REQUEST['channel']) || !isset($_REQUEST['nickname']) || !isset($channels[$_REQUEST['channel']]))
	{
?>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/chat.php')?>" method="get" id="chat-form">
	<dl>
		<dt class="c-kanal"><label for="i-kanal"><?=h(_("Kanal&[chat.php|1]"))?></label></dt>
		<dd class="c-kanal"><select name="channel" id="i-kanal"<?=accesskey_attr(_("Kanal&[chat.php|1]"))?>>
<?php
		foreach($channels as $id=>$info)
		{
?>
			<option value="<?=htmlspecialchars($id)?>"><?=h($info[0])?></option>
<?php
		}
?>
		</select></dd>

		<dt class="c-spitzname"><label for="i-spitzname"><?=h(_("Spitzname&[chat.php|1]"))?></label></dt>
		<dd class="c-spitzname"><input type="text" name="nickname" id="i-spitzname"<?=accesskey_attr(_("Spitzname&[chat.php|1]"))?> /></dd>
	</dl>
	<div><button type="submit"<?=accesskey_attr(_("Verbinden&[chat.php|1]"))?>><?=h(_("Verbinden&[chat.php|1]"))?></button></div>
</form>
<script type="text/javascript">
// <![CDATA[
	document.getElementById('i-spitzname').parentNode.parentNode.appendChild(dt_el = document.createElement('dt'));
	dt_el.className = 'c-neues-fenster';
	dt_el.appendChild(label_el = document.createElement('label'));
	label_el.setAttribute('for', 'i-neues-fenster');
	label_el.appendChild(document.createTextNode('<?=jsentities(_("Chat in neuem Fenster öffnen"))?>'));
	dt_el.parentNode.appendChild(dd_el = document.createElement('dd'));
	input_el = document.createElement('input');
	input_el.type = 'checkbox';
	input_el.id = 'i-neues-fenster';
	dd_el.appendChild(input_el);
	document.getElementById('chat-form').onsubmit = function()
	{
		if(input_el.checked)
		{
			open('<?=global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/chat.php'?>?channel='+encodeURIComponent(document.getElementById('i-kanal').value)+'&nickname='+encodeURIComponent(document.getElementById('i-spitzname').value)+"&popup=1", "_blank", "location=no,menubar=no,resizable=yes,scrollbars=yes,status=yes,toolbar=no");
			return false;
		}
	}
// ]]>
</script>
<p id="chat-hinweis"><?=h(_("Sie erreichen die Kanäle alternativ mit einem beliebigen IRC-Client."))?></p>
<dl id="chat-irc-liste">
<?php
		foreach($channels as $id=>$info)
		{
			if(!isset($info[3])) $info[3] = 6667;
?>
	<dt><?=$info[0]?></dt>
	<dd><a href="irc://<?=htmlspecialchars($info[1])?>:<?=htmlspecialchars($info[3])?>/<?=htmlspecialchars($info[2])?>"><?=sprintf(h(_("%s auf %s, Port %d")), htmlspecialchars($info[2]), htmlspecialchars($info[1]), htmlspecialchars($info[3]))?></a></dd>
<?php
		}
?>
</dl>
<?php
	}
	else
	{
		if($popup)
		{
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=h(_("[LANG]"))?>">
	<head>
		<title><?=h($channels[$_REQUEST['channel']][0])?></title>
		<base href="<?=htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].h_root.'/chat/')?>" />
		<style type="text/css">
			html,body,#chat-applet { width:100%; height:100%; margin:0; padding:0; border-style:none; }
		</style>
	</head>
	<body>
<?php
		}
		else
		{
?>
<h3><?=h($channels[$_REQUEST['channel']][0])?></h3>
<?php
		}
?>
<applet code="IRCApplet.class" archive="irc.jar,pixx.jar" id="chat-applet">
	<param name="CABINETS" value="irc.cab,securedirc.cab,pixx.cab" />
	<param name="nick" value="<?=$_REQUEST['nickname']?>" />
	<param name="fullname" value="S-U-A Java User" />
	<param name="host" value="<?=htmlspecialchars($channels[$_REQUEST['channel']][1])?>" />
	<param name="command1" value="/join <?=htmlspecialchars($channels[$_REQUEST['channel']][2])?>" />
	<param name="gui" value="pixx" />
<?php
		if(isset($channels[$_REQUEST['channel']][3]))
		{
?>
	<param name="port" value="<?=htmlspecialchars($channels[$_REQUEST['channel']][3])?>" />
<?php
		}
?>
	<param name="language" value="english" />
	<param name="quitmessage" value="http://s-u-a.net/" />
	<param name="pixx:color1" value="000000" />
	<param name="pixx:color2" value="777777" />
	<param name="pixx:color3" value="777777" />
	<param name="pixx:color4" value="777777" />
	<param name="pixx:color5" value="777777" />
	<param name="pixx:color6" value="26252B" />
	<param name="pixx:color7" value="999999" />
	<param name="pixx:color8" value="FF0000" />
	<param name="pixx:color9" value="777777" />
	<param name="pixx:color10" value="777777" />
	<param name="pixx:color11" value="777777" />
	<param name="pixx:color12" value="777777" />
	<param name="pixx:color13" value="777777" />
	<param name="pixx:color14" value="777777" />
	<param name="pixx:color15" value="777777" />
	<param name="highlight" value="true" />
	<param name="pixx:highlightnick" value="true" />
	<param name="pixx:highlightcolor" value="8" />
	<param name="pixx:showconnect" value="false" />
	<param name="pixx:showchanlist" value="false" />
	<param name="pixx:showabout" value="false" />
	<param name="pixx:showhelp" value="false" />
</applet>
<?php
		if($popup)
		{
?>
	</body>
</html>
<?php
		}
	}

	home_gui::html_foot();
?>
