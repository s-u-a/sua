<?php
	require('engine/include.php');
	
	$channels = array(
		'sua' => array('S-U-A player&rsquo;s channel', 'irc.gamesurge.net', '#sua'),
		'sua-dev' => array('S-U-A developer&rsquo;s channel', 'irc.epd-me.net', '#sua-dev')
	);
	
	gui::html_head('http://'.$_SERVER['HTTP_HOST'].h_root.'/chat/');
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; <span xml:lang="en">Chat</span></h2>
<?php
	if(!isset($_REQUEST['channel']) || !isset($_REQUEST['nickname']) || !isset($channels[$_REQUEST['channel']]))
	{
?>
<form action="<?=htmlentities(USE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/chat.php')?>" method="get" id="chat-form">
	<dl>
		<dt class="c-kanal"><label for="i-kanal">Kanal</label></dt>
		<dd class="c-kanal"><select name="channel" id="i-kanal">
<?php
		foreach($channels as $id=>$info)
		{
?>
			<option value="<?=htmlspecialchars($id)?>"><?=$info[0]?></option>
<?php
		}
?>
		</select></dd>
		
		<dt class="c-spitzname"><label for="i-spitzname">Spitzname</label></dt>
		<dd class="c-spitzname"><input type="text" name="nickname" id="i-spitzname" /></dd>
	</dl>
	<div><button type="submit">Verbinden</button></div>
</form>
<p id="chat-hinweis">Sie erreichen die Kanäle alternativ mit einem beliebigen <abbr title="Internet Relay Chat" xml:lang="en"><span xml:lang="de">IRC</span></abbr>-<span xml:lang="en">Client</span>.</p>
<dl id="chat-irc-liste">
<?php
		foreach($channels as $id=>$info)
		{
			if(!isset($info[3])) $info[3] = 6667;
?>
	<dt><?=$info[0]?></dt>
	<dd><a href="irc://<?=htmlentities($info[1])?>:<?=htmlentities($info[3])?>/<?=htmlentities($info[2])?>"><?=htmlentities($info[2])?> auf <?=htmlentities($info[1])?>, Port <?=htmlentities($info[3])?></a></dd>
<?php
		}
?>
</dl>
<?php
	}
	else
	{
?>
<h3><?=$channels[$_REQUEST['channel']][0]?></h3>
<applet code="IRCApplet.class" archive="irc.jar,pixx.jar" id="chat-applet">
	<param name="CABINETS" value="irc.cab,securedirc.cab,pixx.cab" />
	<param name="nick" value="<?=$_REQUEST['nickname']?>" />
	<param name="fullname" value="S-U-A Java User" />
	<param name="host" value="<?=htmlentities($channels[$_REQUEST['channel']][1])?>" />
	<param name="command1" value="/join <?=htmlentities($channels[$_REQUEST['channel']][2])?>" />
	<param name="gui" value="pixx" />
<?php
		if(isset($channels[$_REQUEST['channel']][3]))
		{
?>
	<param name="port" value="<?=htmlentities($channels[$_REQUEST['channel']][3])?>" />
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
	}
	
	gui::html_foot();
?>