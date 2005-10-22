<?php
	require('scripts/include.php');

	if(isset($_POST['rundschreiben']) && strlen(trim($_POST['rundschreiben'])) > 0)
	{
		$empfaenger = array();
		foreach($user_array['verbuendete'] as $name)
			$empfaenger[$name] = 7;
		$empfaenger[$_SESSION['username']] = 8;

		$betreff = "B\xc3\xbcndnisrundschreiben";
		if(isset($_POST['betreff']) && strlen(trim($_POST['betreff'])) > 0)
			$betreff = $_POST['betreff'];

		messages::new_message($empfaenger, $_SESSION['username'], $betreff, $_POST['rundschreiben']);
	}

	if(isset($_POST['empfaenger']) && strlen(trim($_POST['empfaenger'])) > 0)
	{
		if(!is_file(DB_PLAYERS.'/'.urlencode($_POST['empfaenger'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_POST['empfaenger'])))
			$buendnis_error = 'Dieser Spieler existiert nicht.';
		elseif($_POST['empfaenger'] == $_SESSION['username'] || in_array($_POST['empfaenger'], $user_array['verbuendete']))
			$buendnis_error = 'Mit diesem Spieler sind Sie bereits verbündet.';
		elseif(isset($user_array['verbuendete_bewerbungen']) && in_array($_POST['empfaenger'], $user_array['verbuendete_bewerbungen']))
			$buendnis_error = 'Es läuft bereits eine Bündnisanfrage zu diesem Spieler.';
		elseif(isset($user_array['verbuendete_anfragen']) && in_array($_POST['empfaenger'], $user_array['verbuendete_anfragen']))
			$buendnis_error = 'Dieser Spieler hält bereits eine Bündnisanfrage zu Ihnen aufrecht.';
		else
		{
			if(!isset($user_array['verbuendete_bewerbungen']))
				$user_array['verbuendete_bewerbungen'] = array();
			$user_array['verbuendete_bewerbungen'][] = $_POST['empfaenger'];
			write_user_array();

			$that_user_array = get_user_array($_POST['empfaenger']);
			if(!isset($that_user_array['verbuendete_anfragen']))
				$that_user_array['verbuendete_anfragen'] = array();
			$that_user_array['verbuendete_anfragen'][] = $_SESSION['username'];
			write_user_array($_POST['empfaenger'], $that_user_array);

			if(isset($_POST['mitteilung']) && strlen(trim($_POST['mitteilung'])) > 0)
				messages::new_message(array($_POST['empfaenger']=>7, $_SESSION['username']=>8), $_SESSION['username'], "Anfrage auf ein B\xc3\xbcndnis", $_POST['mitteilung']);
			else
				messages::new_message(array($_POST['empfaenger']=>7), $_SESSION['username'], "Anfrage auf ein B\xc3\xbcndnis", "Der Spieler ".utf8_htmlentities($_SESSION['username'])." hat Ihnen eine mitteilungslose B\xc3\xbcndnisanfrage gestellt.");

			unset($_POST['empfaenger']);
			if(isset($_POST['mitteilung']))
				unset($_POST['mitteilung']);
		}
	}

	if(isset($_GET['anfrage']) && isset($_GET['annehmen']) && isset($user_array['verbuendete_anfragen']))
	{
		$key = array_search($_GET['anfrage'], $user_array['verbuendete_anfragen']);
		if($key !== false)
		{
			unset($user_array['verbuendete_anfragen'][$key]);
			if($_GET['annehmen'])
			{
				$user_array['verbuendete'][] = $_GET['anfrage'];
				natcasesort($user_array['verbuendete']);
			}
			write_user_array();

			$that_user_array = get_user_array($_GET['anfrage']);
			$that_key = array_search($_SESSION['username'], $that_user_array['verbuendete_bewerbungen']);
			if($that_key !== false)
			{
				unset($that_user_array['verbuendete_bewerbungen'][$that_key]);
				if($_GET['annehmen'])
				{
					$that_user_array['verbuendete'][] = $_SESSION['username'];
					natcasesort($that_user_array['verbuendete']);
				}
				write_user_array($_GET['anfrage'], $that_user_array);
			}
			unset($that_user_array);

			if($_GET['annehmen'])
				messages::new_message(array($_GET['anfrage']=>7), $_SESSION['username'], "B\xc3\xbcndnisanfrage angenommen", "Der Spieler ".utf8_htmlentities($_SESSION['username'])." hat Ihre B\xc3\xbcndnisanfrage angenommen.");
			else
				messages::new_message(array($_GET['anfrage']=>7), $_SESSION['username'], "B\xc3\xbcnsnisanfrage abgelehnt", "Der Spieler ".utf8_htmlentities($_SESSION['username'])." hat Ihre B\xc3\xbcndnisanfrage abgelehnt.");
		}
	}

	if(isset($_GET['bewerbung']) && isset($user_array['verbuendete_bewerbungen']))
	{
		$key = array_search($_GET['bewerbung'], $user_array['verbuendete_bewerbungen']);
		if($key !== false)
		{
			unset($user_array['verbuendete_bewerbungen'][$key]);
			write_user_array();

			$that_user_array = get_user_array($_GET['bewerbung']);
			$that_key = array_search($_SESSION['username'], $that_user_array['verbuendete_anfragen']);
			if($that_key !== false)
			{
				unset($that_user_array['verbuendete_anfragen'][$that_key]);
				write_user_array($_GET['bewerbung'], $that_user_array);
			}
			unset($that_user_array);

			messages::new_message(array($_GET['bewerbung']=>7), $_SESSION['username'], "B\xc3\xbcndnisbewerbung zur\xc3\xbcckgezogen", "Der Spieler ".utf8_htmlentities($_SESSION['username'])." hat seine B\xc3\xbcndnisbewerbung bei Ihnen zur\xc3\xbcckgezogen.");
		}
	}

	if(isset($_GET['kuendigen']) && isset($user_array['verbuendete']))
	{
		$key = array_search($_GET['kuendigen'], $user_array['verbuendete']);
		if($key !== false)
		{
			unset($user_array['verbuendete'][$key]);
			write_user_array();

			$that_user_array = get_user_array($_GET['kuendigen']);
			$that_key = array_search($_SESSION['username'], $that_user_array['verbuendete']);
			if($that_key !== false)
			{
				unset($that_user_array['verbuendete'][$that_key]);
				write_user_array($_GET['kuendigen'], $that_user_array);
			}
			unset($that_user_array);

			messages::new_message(array($_GET['kuendigen']=>7), $_SESSION['username'], "B\xc3\xbcndnis gek\xc3\xbcndigt", "Der Spieler ".utf8_htmlentities($_SESSION['username'])." hat sein B\xc3\xbcndnis mit Ihnen gek\xc3\xbcndigt.");
		}
	}

	login_gui::html_head();

	if(isset($user_array['verbuendete_anfragen']) && count($user_array['verbuendete_anfragen']) > 0)
	{
?>
<h3>Anfragen von anderen Spielern</h3>
<dl class="buendnisse-anfragen buendnisse-liste">
<?php
		foreach($user_array['verbuendete_anfragen'] as $anfrage)
		{
?>
	<dt><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($anfrage))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($anfrage)?></a></dt>
	<dd><ul>
		<li><a href="verbuendete.php?anfrage=<?=htmlentities(urlencode($anfrage))?>&amp;annehmen=1">Annehmen</a></li>
		<li><a href="verbuendete.php?anfrage=<?=htmlentities(urlencode($anfrage))?>&amp;annehmen=0">Ablehnen</a></li>
	</ul></dd>
<?php
		}
?>
</dl>
<?php
	}
	if(isset($user_array['verbuendete_bewerbungen']) && count($user_array['verbuendete_bewerbungen']) > 0)
	{
?>
<h3>Bewerbungen bei anderen Spielern</h3>
<dl class="buendnisse-bewerbungen buendnisse-liste">
<?php
		foreach($user_array['verbuendete_bewerbungen'] as $bewerbung)
		{
?>
	<dt><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($bewerbung))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($bewerbung)?></a></dt>
	<dd><ul>
		<li><a href="verbuendete.php?bewerbung=<?=htmlentities(urlencode($bewerbung))?>">Zurückziehen</a></li>
	</ul></dd>
<?php
		}
?>
</dl>
<?php
	}
?>
<h2>Bündnisse</h2>
<?php
	if(count($user_array['verbuendete']) <= 0)
	{
?>
<p class="buendnisse-keine">
	Sie sind derzeit mit keinen Spielern verbündet.
</p>
<?php
	}
	else
	{
?>
<dl class="buendnisse buendnisse-liste">
<?php
		foreach($user_array['verbuendete'] as $name)
		{
?>
	<dt><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($name))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($name)?></a></dt>
	<dd><ul>
		<li><a href="verbuendete.php?kuendigen=<?=htmlentities(urlencode($name))?>" onclick="return confirm('Wollen Sie das Bündnis mit dem Spieler <?=utf8_jsentities($name)?> wirklich kündigen?');">Kündigen</a></li>
	</ul></dd>
<?php
		}
?>
</dl>
<form action="verbuendete.php" method="post" class="buendnisse-rundschreiben">
	<fieldset>
		<legend>Bündnisrundschreiben</legend>
		<dl>
			<dt class="c-betreff"><label for="betreff-input">Betreff</label></dt>
			<dd class="c-betreff"><input type="text" name="betreff" id="betreff-input" /></dd>

			<dt class="c-text"><label for="text-textarea">Text</label></dt>
			<dd class="c-text"><textarea name="rundschreiben" id="text-textarea" rows="6" cols="35"></textarea></dd>
		</dl>
		<div><button type="submit">Rundschreiben verschicken</button></div>
	</fieldset>
</form>
<?php
	}
?>
<h3>Neues Bündnis eingehen</h3>
<?php
	if(isset($buendnis_error) && strlen(trim($buendnis_error)) > 0)
	{
?>
<p class="error">
	<?=htmlentities($buendnis_error)."\n"?>
</p>
<?php
	}
?>
<form action="verbuendete.php" method="post" class="buendnisse-eingehen">
	<dl>
		<dt class="c-spieler"><label for="spieler-input">Spieler</label></dt>
		<dd class="c-spieler"><input type="text" name="empfaenger" id="spieler-input" value="<?=(isset($_POST['empfaenger']) ? utf8_htmlentities($_POST['empfaenger']) : '')?>" /></dd>

		<dt class="c-mitteilung"><label for="mitteilung-textarea">Mitteilung</label></dt>
		<dd class="c-mitteilung"><textarea rows="5" cols="30" name="mitteilung" id="mitteilung-textarea"><?=(isset($_POST['mitteilung']) ? preg_replace("/[\t\r\n]/e", '\'&#\'.ord(\'$0\').\';\'', utf8_htmlentities($_POST['mitteilung'])) : '')?></textarea></dd>
	</dl>
	<div><button type="submit">Anfrage absenden</button></div>
</form>
<?php
	login_gui::html_foot();
?>