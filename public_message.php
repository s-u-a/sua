<?php
	require('engine/include.php');
?>
<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=h(_("[LANG]"))?>">
	<head>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<title><?=h(_("[title_abbr_full]"))?></title>
<?php
	$skins = get_skins();
	if($skins && isset($skins['default']) && count($skins['default'][1]) > 0)
	{
		$keys = array_keys($skins['default'][1]);
		$sub_skin = array_shift($keys);
?>
		<link rel="stylesheet" href="<?=h_root?>/login/style/skin.php?skin=default&amp;type=<?=htmlspecialchars($sub_skin)?>" type="text/css" />
<?php
	}
?>
	</head>
	<body><div id="content-1"><div id="content-2"><div id="content-3"><div id="content-4"><div id="content-5"><div id="content-6"><div id="content-7"><div id="content-8"><div id="content-9"><div id="content-10"><div id="content-11"><div id="content-12"><div id="content-13">
		<h1><?=h(_("Öffentliche Nachricht"))?></h1>
<?php
	$databases = get_databases();
	if(isset($_GET['database']) && isset($databases[$_GET['database']]))
		define_globals($_GET['database']);
	if(!isset($_GET['database']) || !isset($databases[$_GET['database']]) || !isset($_GET['id']) || !PublicMessage::publicMessageExists($_GET['id']))
	{
?>
		<p class="error"><?=h(_("Die gewünschte Nachricht existiert nicht."))?></p>
<?php
	}
	else
	{
		$message = Classes::PublicMessage($_GET['id']);
?>
		<dl class="nachricht-informationen type-<?=htmlspecialchars($message->type())?><?=$message->html() ? ' html' : ''?>">
<?php
		if($message->from() != '')
		{
?>
			<dt class="c-absender"><?=h(_("Absender"))?></dt>
			<dd class="c-absender"><?=htmlspecialchars($message->from())?></dd>
<?php
		}
?>
			<dt class="c-empfaenger"><?=h(_("Empfänger"))?></dt>
			<dd class="c-empfaenger"><?=htmlspecialchars($message->to())?></dd>

			<dt class="c-betreff"><?=h(_("Betreff"))?></dt>
			<dd class="c-betreff"><?=htmlspecialchars($message->subject())?></dd>

			<dt class="c-zeit"><?=h(_("Zeit"))?></dt>
			<dd class="c-zeit"><?=date(_('H:i:s, Y-m-d'), $message->time())?></dd>

			<dt class="c-nachricht"><?=h(_("Nachricht"))?></dt>
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
