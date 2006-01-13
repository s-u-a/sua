<?php
	require('engine/include.php');
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<title xml:lang="en">S-U-A &ndash; Stars Under Attack</title>
		<link rel="stylesheet" href="<?=h_root?>/login/style/skin.php?simple_blue_fixed" type="text/css" />
	</head>
	<body><div id="content-1"><div id="content-2"><div id="content-3"><div id="content-4"><div id="content-5"><div id="content-6"><div id="content-7"><div id="content-8"><div id="content-9"><div id="content-10"><div id="content-11"><div id="content-12"><div id="content-13">
		<h1>Öffentliche Nachricht</h1>
<?php
	$databases = get_databases();
	if(isset($_GET['database']) && isset($databases[$_GET['database']]))
		define_globals($databases[$_GET['database']][0]);
	if(!isset($_GET['database']) || !isset($databases[$_GET['database']]) || !isset($_GET['id']) || !PublicMessage::publicMessageExists($_GET['id']))
	{
?>
		<p class="error">Die gewünschte Nachricht existiert nicht.</p>
<?php
	}
	else
	{
		$message = Classes::PublicMessage($_GET['id']);
?>
		<dl class="nachricht-informationen type-<?=utf8_htmlentities($message->type())?><?=$message->html() ? ' html' : ''?>">
<?php
		if($message->from() != '')
		{
?>
			<dt class="c-absender">Absender</dt>
			<dd class="c-absender"><?=utf8_htmlentities($message->from())?></dd>
<?php
		}
?>
			<dt class="c-empfaenger">Empfänger</dt>
			<dd class="c-empfaenger"><?=utf8_htmlentities($message->to())?></dd>

			<dt class="c-betreff">Betreff</dt>
			<dd class="c-betreff"><?=utf8_htmlentities($message->subject())?></dd>

			<dt class="c-zeit">Zeit</dt>
			<dd class="c-zeit"><?=date('H:i:s, Y-m-d', $message->time())?></dd>

			<dt class="c-nachricht">Nachricht</dt>
			<dd class="c-nachricht">
<?php
		print("\t\t\t\t".preg_replace("/\r\n|\r|\n/", "\n\t\t\t\t", $message->text()));
?>
			</dd>
		</dl>
<?php
	}
?>
		</div></div></div></div></div></div></div></div></div></div></div></div></div>
	</body>
</html>