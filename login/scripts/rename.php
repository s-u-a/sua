<?php
	require('include.php');

	$planet_error = false;
	if(isset($_POST['planet_name']))
	{
		if(trim($_POST['planet_name']) == '')
			$_POST['planet_name'] = $me->planetName();
		elseif(strlen($_POST['planet_name']) <= 24)
			$planet_error = !$me->planetName($_POST['planet_name']);
	}

	# Herausfinden, ob eigene Flotten zu/von diesem Planeten unterwegs sind
	$flotte_unterwegs = $me->checkOwnFleetWithPlanet();
	$planets = $me->getPlanetsList();

	if(isset($_POST['act_planet']) && isset($_POST['password']) && !$flotte_unterwegs && count($planets) > 1)
	{
		if(!$me->checkPassword($_POST['password']))
			$aufgeben_error = 'Sie haben ein falsches Passwort eingegeben.';
		elseif($_POST['act_planet'] != $_SESSION['act_planet'])
			$aufgeben_error = 'Sicherheit: Da inzwischen der Planet gewechselt wurde, hätten Sie es wohl bereut, wenn Sie den aktuellen aufgegeben hätten.';
		else
		{
			$me->removePlanet($_SESSION['act_planet']);
			$_SESSION['act_planet'] = $me->getActivePlanet();
			$planets = $me->getPlanetsList();
		}
	}

	if(isset($_GET['down']))
	{
		$me->movePlanetDown($_GET['down']);
		$_SESSION['act_planet'] = $me->getActivePlanet();
		$planets = $me->getPlanetsList();
	}
	if(isset($_GET['up']))
	{
		$me->movePlanetUp($_GET['up']);
		$_SESSION['act_planet'] = $me->getActivePlanet();
		$planets = $me->getPlanetsList();
	}

	login_gui::html_head();

	if($planet_error)
	{
?>
<p class="error">
	Datenbankfehler.
</p>
<?php
	}
	elseif(isset($_POST['planet_name']) && strlen($_POST['planet_name']) > 24)
	{
?>
<p class="error">
	Der Name darf maximal 24&nbsp;Bytes lang sein.
</p>
<?php
	}
?>
<form action="rename.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post">
	<fieldset>
		<legend>Planeten umbenennen</legend>
		<dl>
			<dt><label for="name"><kbd>N</kbd>euer Name</label></dt>
			<dd><input type="text" id="name" name="planet_name" value="<?=utf8_htmlentities($me->planetName())?>" maxlength="24" accesskey="n" tabindex="1" /></dd>
		</dl>
		<div><button type="submit" accesskey="u" tabindex="2"><kbd>U</kbd>mbenennen</button></div>
	</fieldset>
</form>
<?php
	if($flotte_unterwegs || count($planets) <= 1)
	{
?>
<p class="planeten-nicht-aufgeben">
	Sie können diesen Planeten derzeit nicht aufgeben, da Flottenbewegungen Ihrerseits von/zu diesem Planeten unterwegs sind oder dies Ihr einziger Planet ist.
</p>
<?php
	}
	else
	{
		if(isset($aufgeben_error) && trim($aufgeben_error) != '')
		{
?>
<p class="error">
	<?=htmlentities($aufgeben_error)."\n"?>
</p>
<?php
		}
?>
<form action="<?=htmlentities(USE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/login/scripts/rename.php?'.urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post">
	<fieldset>
		<legend>Planeten aufgeben<input type="hidden" name="act_planet" value="<?=htmlentities($_SESSION['act_planet'])?>" /></legend>
		<dl>
			<dt><label for="password">Passwort</label></dt>
			<dd><input type="password" id="password" name="password" tabindex="3" /></dd>
		</dl>
		<div><button type="submit" tabindex="4">Aufgeben</button></div>
	</fieldset>
</form>
<?php
	}

	if(count($planets) > 1)
	{
?>
<fieldset class="planeten-reihenfolge">
	<legend>Planeten-Reihenfolge</legend>
	<ol>
<?php
		$active_planet = $me->getActivePlanet();
		foreach($planets as $i=>$planet)
		{
			$me->setActivePlanet($planet);
?>
		<li><?=utf8_htmlentities($me->planetName())?> <span class="pos">(<?=utf8_htmlentities($me->getPosString())?>)</span><span class="aktionen"><?php if($i != 0){?> &ndash; <a href="rename.php?up=<?=htmlentities(urlencode($planet))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.session_id())?>" class="hoch">[Hoch]</a><?php } if($i != count($planets)-1){?> &ndash; <a href="rename.php?down=<?=htmlentities(urlencode($planet))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.session_id())?>" class="runter">[Runter]</a><?php }?></span></li>
<?php
		}
		$me->setActivePlanet($active_planet);
?>
	</ol>
</fieldset>
<?php
	}

	login_gui::html_foot();
?>