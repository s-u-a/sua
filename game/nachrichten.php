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
	 * Nachrichtensystem
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('include.php');

	if(isset($_GET["type"]) && isset($_GET["message"]))
		$gui->setOption("ignore_messages", array($_GET["message"]));
	elseif(isset($_GET["type"]) && !isset($_GET["message"]) && (isset($_POST["delete"]) || isset($_POST["read"]) || isset($_POST["archive"])) && isset($_POST["message"]))
		$gui->setOption("ignore_messages", array_keys($_POST["message"]));
	$gui->init();

	if(isset($_GET['to']))
	{
		# Neue Nachricht verfassen
?>
<h2><a href="nachrichten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Zurück zur Nachrichtenkategorienübersicht"))?>"<?=L::accesskeyAttr(_("Nachrichten [&W][login/nachrichten.php|1]"))?> tabindex="<?=$tabindex+4?>"><?=L::h(_("Nachrichten [&W][login/nachrichten.php|1]"))?></a></h2>
<?php
		$error = '';
		$show_form = true;

		if(isset($_POST['empfaenger']) && isset($_POST['betreff']) && isset($_POST['inhalt']))
		{
			# Nachricht versenden, versuchen

			$_POST['empfaenger'] = trim($_POST['empfaenger']);

			if(!isset($_SESSION["admin_username"]) && !User::exists($_POST['empfaenger']))
				$error = _('Der Empfänger, den Sie eingegeben haben, existiert nicht.');
			elseif(!isset($_SESSION["admin_username"]) && strtolower($_POST['empfaenger']) == strtolower($me->getName()))
				$error = _('Sie können sich nicht selbst eine Nachricht schicken.');
			elseif(!isset($_SESSION["admin_username"]) && strlen($_POST['betreff']) > 30)
				$error = _('Der Betreff darf maximal 30 Bytes lang sein.');
			elseif(strlen($_POST['inhalt']) <= 0)
				$error = _('Sie müssen eine Nachricht eingeben.');
			else
			{
				# Nachricht versenden
				$message = Classes::Message(Message::create());
				$message->text($_POST['inhalt']);
				$message->subject($_POST['betreff']);
				$message->from($me->getName());

				if(isset($_SESSION["admin_username"]))
				{
					foreach(preg_split("/\r?\n/", $_POST["empfaenger"]) as $empf)
					{
						if(User::exists($empf))
							$message->addUser($empf, 6);
					}
				}
				else
				{
					$message->addUser(User::datasetName($_POST['empfaenger']), 6);
					$message->addUser($me->getName(), 8);
				}
?>
<p class="successful"><?=L::h(_("Die Nachricht wurde erfolgreich versandt."))?></p>
<?php
				$show_form = false;
				unset($message);
			}
		}

		if(trim($error) != '')
		{
?>
<p class="error"><?=htmlspecialchars($error)?></p>
<?php
		}

		if($show_form)
		{
			# Formular zum Absenden anzeigen
?>
<form action="nachrichten.php?to=&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="nachrichten-neu" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'<?=JS::jsentities(_("Doppelklickschutz: Sie haben ein zweites Mal auf „Absenden“ geklickt. Dadurch wird die Nachricht auch ein zweites Mal abgeschickt. Sind Sie sicher, dass Sie diese Aktion durchführen wollen?"))?>\');');">
	<fieldset>
		<legend><?=L::h(_("Nachricht verfassen"))?></legend>
		<dl class="form">
			<dt class="c-empfaenger"><label for="empfaenger-input"><?=L::h(_("Empfänger [&Z][login/nachrichten.php|1]"))?></label></dt>
<?php
			$empfaenger = $_GET['to'];
			if(isset($_POST['empfaenger']))
				$empfaenger = $_POST['empfaenger'];
			$betreff = '';
			if(isset($_GET['subject']))
				$betreff = $_GET['subject'];
			if(isset($_POST['betreff']))
				$betreff = $_POST['betreff'];
			if(!isset($_SESSION["admin_username"]))
			{
?>
			<dd class="c-empfaenger"><input type="text" id="empfaenger-input" name="empfaenger" value="<?=htmlspecialchars($empfaenger)?>" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Empfänger [&Z][login/nachrichten.php|1]"))?> /></dd>
<?php
			}
			else
			{
?>
			<dd class="c-empfaenger"><textarea id="empfaenger-input" name="empfaenger" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Empfänger [&Z][login/nachrichten.php|1]"))?>><?=htmlspecialchars($empfaenger)?></textarea></dd>
<?php
			}
?>

			<dt class="c-betreff"><label for="betreff-input"><?=L::h(_("Betreff [&J][login/nachrichten.php|1]"))?></label></dt>
			<dd class="c-betreff"><input type="text" id="betreff-input" name="betreff" value="<?=htmlspecialchars($betreff)?>" maxlength="30" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Betreff [&J][login/nachrichten.php|1]"))?> /></dd>

			<dt class="c-inhalt"><label for="inhalt-input"><?=L::h(_("Inhalt [&X][login/nachrichten.php|1]"))?></label></dt>
			<dd class="c-inhalt"><textarea id="inhalt-input" name="inhalt" cols="50" rows="10" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Inhalt [&X][login/nachrichten.php|1]"))?>><?=isset($_POST['inhalt']) ? preg_replace("/[\n\r\t]/e", '\'&#\'.ord(\'$0\').\';\'', htmlspecialchars($_POST['inhalt'])) : ''?></textarea></dd>
		</dl>
	</fieldset>
	<div class="button"><button type="submit"<?=L::accesskeyAttr(_("Abse&nden[login/nachrichten.php|1]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Abse&nden[login/nachrichten.php|1]"))?></button></div>
</form>
<?php
			$tabindex++; # Oben für den Back-Link verwendet

			if(!isset($_SESSION["admin_username"]))
			{ # Autocomplete geht im Moment nicht in Textareas
?>
<script type="text/javascript">
	// Autocompletion des Empfaengers
	activate_users_list(document.getElementById('empfaenger-input'));
</script>
<?php
			}
		}
	}
	elseif(isset($_GET['type']))
	{
		# Nachrichtentyp wurde bereits ausgewaehlt, Nachricht oder Nachrichtenliste anzeigen

		if(isset($_GET['message']))
		{
			# Nachricht anzeigen
?>
<h2><a href="nachrichten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Zurück zur Nachrichtenkategorienübersicht"))?>" tabindex="<?=$tabindex+6?>"<?=L::accesskeyAttr(_("Nachrichten [&W][login/nachrichten.php|2]"))?>><?=L::h(_("Nachrichten [&W][login/nachrichten.php|2]"))?></a>: <a href="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(sprintf(_("Zurück zur Nachrichtenübersicht: %s"), _("[message_".$_GET["type"]."]")))?>" tabindex="<?=$tabindex+5?>"<?=L::accesskeyAttr(_("%s [&O][login/nachrichten.php|2]"))?>><?=L::h(sprintf(_("%s [&O][login/nachrichten.php|2]"), _("[message_".$_GET['type']."]")))?></a></h2>
<?php
			$message = Classes::Message($_GET['message']);
			if(!$message->mayRead($me->getName()))
			{
?>
<p class="error"><?=L::h(_("Diese Nachricht existiert nicht."))?></p>
<?php
			}
			else
			{
				# Nachricht kann angezeigt werden

				# Als gelesen markieren
				if(!isset($_SESSION['admin_username']) && $message->messageStatus($me->getName()) == Message::STATUS_NEU)
					$message->messageStatus($me->getName(), Message::STATUS_ALT);

				$type = $message->messageType($me->getName());

				# Vorige und naechste ungelesene Nachricht bestimmen
				$messages = Message::getUserMessages($me->getName(), $type, Message::STATUS_NEU);
				$this_key = array_search($_GET['message'], $messages);
				if($this_key > 0)
					$unread_prev = $messages[$this_key-1];
				else
					$unread_prev = false;
				if($this_key < count($messages)-1)
					$unread_next = $messages[$this_key+1];
				else
					$unread_next = false;

				if($unread_next !== false || $unread_prev !== false)
				{
					# Vorige und naechste verlinken
?>
<ul class="ungelesene-nachrichten fast-seek">
<?php
					if($unread_prev !== false)
					{
?>
	<li class="c-prev"><a href="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;message=<?=htmlspecialchars(urlencode($unread_prev))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Vorige &ungelesene Nachricht[login/nachrichten.php|2]"), false)?>"<?=L::accesskeyAttr(_("Vorige &ungelesene Nachricht[login/nachrichten.php|2]"))?> tabindex="<?=$tabindex+4?>"><?=L::h(_("←"))?></a></li>
<?php
					}
					if($unread_next !== false)
					{
?>
	<li class="c-next"><a href="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;message=<?=htmlspecialchars(urlencode($unread_next))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Nächste ungelesene Nachricht [&Q][login/nachrichten.php|2]"), false)?>"<?=L::accesskeyAttr(_("Nächste ungelesene Nachricht [&Q][login/nachrichten.php|2]"))?> tabindex="<?=$tabindex+3?>"><?=L::h(_("→"))?></a></li>
<?php
					}
?>
</ul>
<?php
				}
?>
<dl class="nachricht-informationen type-<?=htmlspecialchars($type)?><?=$message->html() ? ' html' : ''?>">
<?php
				if(trim($message->from()) != '')
				{
?>
	<dt class="c-absender"><?=L::h(_("Absender"))?></dt>
	<dd class="c-absender"><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($message->from()))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($message->from())?></a></dd>

<?php
				}
				if($type == Message::TYPE_POSTAUSGANG && $message->getRecipients())
				{
?>
	<dt class="c-empfaenger"><?=L::h(_("Empfänger"))?></dt>
	<dd class="c-empfaenger"><ul>
<?php
					foreach($message->getRecipients() as $recipient)
					{
?>
		<li><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($recipient)."&".global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($recipient)?></a></li>
<?php
					}
?>
	</ul></dd>
<?php
				}

				$subject = trim($message->subject());
				if(strlen($subject) <= 0) $subject = "Kein Betreff";
?>
	<dt class="c-betreff"><?=L::h(_("Betreff"))?></dt>
	<dd class="c-betreff"><?=htmlspecialchars($subject)?></dd>

	<dt class="c-zeit"><?=L::h(_("Zeit"))?></dt>
	<dd class="c-zeit"><?=date(_('H:i:s, Y-m-d'), $message->getTime())?></dd>

	<dt class="c-nachricht"><?=L::h(_("Nachricht"))?></dt>
	<dd class="c-nachricht">
<?php
				$text = $message->text();

				if($message->html())
				{
					# Session-ID an Links anhaengen
					$text = preg_replace('/(<a [^>]*href=")([^"]*)(")/ei', 'F::replMessageLinks("\\1", "\\2", "\\3")', $text);
				}

				print("\t\t".preg_replace("/\r\n|\r|\n/", "\n\t\t", $text));
?>
	</dd>
</dl>
<?php
				if($type == Message::TYPE_VERBUENDETE)
				{
?>
<ul class="nachrichten-verbuendeten-links actions">
	<li class="c-verbuendete"><a href="verbuendete.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Zur Verbündetenseite&[login/nachrichten.php|2]"))?>><?=L::h(_("Zur Verbündetenseite&[login/nachrichten.php|2]"))?></a></li>
	<li class="c-allianz"><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Zur Allianzseite&[login/nachrichten.php|2]"))?>><?=L::h(_("Zur Allianzseite&[login/nachrichten.php|2]"))?></a></li>
</ul>
<?php
				}

				if($message->from() != '' && $message->from() != $me->getName())
				{
					# Bei Nachrichten im Postausgang ist die Antwort nicht moeglich
					$re_betreff = $message->subject();
					if(substr($re_betreff, 0, 4) != 'Re: ')
						$re_betreff = 'Re: '.$re_betreff;
?>
<form action="nachrichten.php" method="get" class="nachricht-antworten-formular">
	<div class="button">
		<?=global_setting("URL_FORMULAR")."\n"?>
		<input type="hidden" name="to" value="<?=htmlspecialchars($message->from())?>" />
		<input type="hidden" name="subject" value="<?=htmlspecialchars($re_betreff)?>" />
		<button type="submit"<?=L::accesskeyAttr(_("Ant&worten[login/nachrichten.php|2]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Ant&worten[login/nachrichten.php|2]"))?></button>
	</div>
</form>
<?php
				}
				else
					$tabindex++;
?>
<form action="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="nachricht-loeschen-formular">
	<div><input type="hidden" name="message[<?=htmlspecialchars($_GET['message'])?>]" value="on" /><input type="submit" name="delete" tabindex="<?=$tabindex++?>" value="<?=L::h(_("Lösche&n[login/nachrichten.php|2]"), false)?>"<?=L::accesskeyAttr(_("Lösche&n[login/nachrichten.php|2"))?> /> <input type="submit" name="archive" tabindex="<?=$tabindex++?>" value="<?=L::h(_("Archivieren&[login/nachrichten.php|2]"))?>"<?=L::accesskeyAttr(_("Archivieren&[login/nachrichten.php|2]"))?> /></div>
</form>
<?php
				$tabindex += 4;

				if(isset($_POST['weiterleitung-to']))
				{
					$_POST['weiterleitung-to'] = trim($_POST['weiterleitung-to']);

					if(!User::exists($_POST['weiterleitung-to']))
					{
?>
<p class="error"><?=L::h(_("Der Empfänger, den Sie eingegeben haben, existiert nicht."))?></p>
<?php
					}
					elseif($_POST['weiterleitung-to'] == $me->getName())
					{
?>
<p class="error"><?=L::h(_("Sie können sich nicht selbst eine Nachricht schicken."))?></p>
<?php
					}
					else
					{
						$weiterleitung_message = Classes::Message(Message::create());
						$weiterleitung_text = '';
						$to_user = Classes::User($_POST["weiterleitung_to"]);
						if($message->html())
							$weiterleitung_text .= "<p class=\"weitergeleitete-nachricht\">\n\t";
						$weiterleitung_text .= "--- ".($message->html() ? h($to_user->_("Weitergeleitete Nachricht")) : $to_user->_("Weitergeleitete Nachricht"));
						if(trim($message->from()) != '')
						{
							$weiterleitung_text .= ", ".($message->html() ? h($to_user->_("Absender")) : $to_user->_("Absender")).": ";
							if($message->html())
								$weiterleitung_text .= htmlspecialchars($message->from());
							else
								$weiterleitung_text .= $message->from();
						}
						if($message->getTime())
							$weiterleitung_text .= ", ".sprintf($message->html() ? h($to_user->_("Sendezeit: %s")) : $to_user->_("Sendezeit: %s"), date($to_user->_('H:i:s, Y-m-d'), $message->getTime()));
						$weiterleitung_text .= " ---\n";
						if($message->html())
							$weiterleitung_text .= "</p>";
						$weiterleitung_text .= "\n ";
						$weiterleitung_message->text($weiterleitung_text.$message->rawText());
						$weiterleitung_message->subject('Fwd: '.$message->subject());
						$weiterleitung_message->from($me->getName());
						$weiterleitung_message->addUser(User::datasetName($to_user->getName()));
						$weiterleitung_message->html($message->html());
?>
<p class="successful"><?=L::h(_("Die Nachricht wurde erfolgreich weitergeleitet."))?></p>
<?php
						unset($_POST['weiterleitung-to']);
						unset($weiterleitung_message);
					}
				}

				if(isset($_GET['publish']) && $_GET['publish'] && !PublicMessage::publicMessageExists($_GET['message']))
				{
					$public_message = Classes::PublicMessage($_GET['message']);
					$public_message->createFromMessage($message);
					if($_GET['type'] != 1)
						$public_message->to($me->getName());
					else
						$public_message->subject('');
					$public_message->type($type);
					unset($public_message);
				}

				if(PublicMessage::publicMessageExists($_GET['message']))
				{
					$host = Config::get_default_hostname();
?>
<p id="nachricht-veroeffentlichen"><?=sprintf(h(_("Sie können diese Nachricht öffentlich verlinken: %s")), "<a href=\"http://".htmlspecialchars($host.global_setting("h_root"))."/public_message.php?id=".htmlspecialchars(urlencode($_GET['message']))."&amp;database=".htmlspecialchars(urlencode($_SESSION['database']))."\">http://".htmlspecialchars($host.global_setting("h_root"))."/public_message.php?id=".htmlspecialchars(urlencode($_GET['message']))."&amp;database=".htmlspecialchars(urlencode($_SESSION['database']))."</a>")?></p>
<?php
				}
				else
				{
?>
<ul id="nachricht-veroeffentlichen" class="possibilities">
	<li><a href="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;message=<?=htmlspecialchars(urlencode($_GET['message']))?>&amp;publish=1&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>#nachricht-veroeffentlichen"<?=L::accesskeyAttr(_("Nachricht veröffentlichen&[login/nachrichten.php|2]"))?>><?=L::h(_("Nachricht veröffentlichen&[login/nachrichten.php|2]"))?></a></li>
</ul>
<?php
				}
?>
<form action="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;message=<?=htmlspecialchars(urlencode($_GET['message']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>#nachricht-weiterleiten-formular" method="post" id="nachricht-weiterleiten-formular" class="nachricht-weiterleiten-formular">
	<fieldset>
		<legend><?=L::h(_("Nachricht weiterleiten"))?></legend>
		<dl class="form">
			<dt><label for="empfaenger-input"><?=L::h(_("Empfänger [&X][login/nachrichten.php|2]"))?></label></dt>
			<dd><input type="text" id="empfaenger-input" name="weiterleitung-to" value="<?=isset($_POST['weiterleitung-to']) ? htmlspecialchars($_POST['weiterleitung-to']) : ''?>"<?=L::accesskeyAttr(_("Empfänger [&X][login/nachrichten.php|2]"))?> tabindex="<?=$tabindex++?>" /></dd>
		</dl>
		<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Weiterleiten&[login/nachrichten.php|2]"))?>><?=L::h(_("Weiterleiten&[login/nachrichten.php|2]"))?></button></div>
	</fieldset>
</form>
<script type="text/javascript">
	activate_users_list(document.getElementById('empfaenger-input'));
</script>
<?php
			}
		}
		else
		{
			# Nachrichtenuebersicht einer Kategorie anzeigen
?>
<h2><a href="nachrichten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Zurück zur Nachrichtenkategorienübersicht"))?>"<?=L::accesskeyAttr(_("Nachrichten [&W][login/nachrichten.php|3]"))?> tabindex="<?=$tabindex+4?>"><?=L::h(_("Nachrichten [&W][login/nachrichten.php|3]"))?></a>: <?=L::h(_("[message_".$_GET['type']."]"))?></h2>
<?php
			if(isset($_POST['message']) && is_array($_POST['message']))
			{
				if(isset($_POST['read']))
				{
					# Als gelesen markieren
					foreach($_POST['message'] as $message_id=>$v)
					{
						$message = Classes::Message($message_id);
						if($message->messageStatus($me->getName()) == Message::STATUS_NEU)
							$message->messageStatus($me->getName(), Message::STATUS_ALT);
					}
				}
				elseif(isset($_POST['delete']))
				{
					# Loeschen
					foreach($_POST['message'] as $message_id=>$v)
						Classes::Message($message_id)->removeUser($me->getName());
				}
				elseif(isset($_POST['archive']))
				{
					# Archivieren
					foreach($_POST['message'] as $message_id=>$v)
						$message->messageStatus($me->getName(), Message::STATUS_ARCHIV);
				}
			}

			$messages_list = Message::getUserMessages($me->getName(), $_GET["type"]);
			if(count($messages_list) > 0)
			{
?>
<script type="text/javascript">
// <![CDATA[
	function toggle_selection()
	{
		var formular = document.getElementById('nachrichten-liste').elements;
		for(var i=0; i<formular.length; i++)
		{
			if(formular[i].checked != undefined)
				formular[i].checked = !formular[i].checked;
		}
	}
// ]]>
</script>
<form action="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="nachrichten-liste type-<?=htmlspecialchars($_GET['type'])?>" id="nachrichten-liste">
	<table>
		<thead>
			<tr>
				<th class="c-auswaehlen"></th>
				<th class="c-betreff"><?=L::h(_("Betreff"))?></th>
				<th class="c-<?=$_GET["type"] == Message::TYPE_POSTAUSGANG ? "empfaenger" : "absender"?>"><?=$_GET["type"] == Message::TYPE_POSTAUSGANG ? h(_("Empfänger")) : h(_("Absender"))?></th>
				<th class="c-datum"><?=L::h(_("Datum"))?></th>
			</tr>
		</thead>
		<tbody>
<?php
				$tabindex_save = $tabindex+5;
				foreach($messages_list as $message_id)
				{
					$message = Classes::Message($message_id);
					$status = $message->messageStatus($me->getName());
					if($status === Message::STATUS_ARCHIV)
						$class = 'archiviert type-em';
					elseif($status == Message::STATUS_NEU && $_GET['type'] != Message::TYPE_POSTAUSGANG)
						$class = 'neu type-strong';
					else
						$class = 'alt type-weak';

					$subject = trim($message->subject());
					if(strlen($subject) <= 0) $subject = "Kein Betreff";
?>
			<tr class="<?=$class?>">
				<td class="c-auswaehlen"><input type="checkbox" name="message[<?=htmlspecialchars($message_id)?>]" tabindex="<?=$tabindex_save++?>" /></td>
				<td class="c-betreff"><a href="nachrichten.php?type=<?=htmlspecialchars(urlencode($_GET['type']))?>&amp;message=<?=htmlspecialchars(urlencode($message_id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>"><?=htmlspecialchars($subject)?></a></td>
				<td class="c-<?=$_GET["type"] == Message::TYPE_POSTAUSGANG ? "empfaenger" : "absender"?>"><?=htmlspecialchars($_GET["type"] == Message::TYPE_POSTAUSGANG ? implode(_(", "), $message->getRecipients()) : $message->from())?></td>
				<td class="c-datum"><?=date(_('H:i:s, Y-m-d'), $message->getTime())?></td>
			</tr>
<?php
				}
				$me->write();
?>
		</tbody>
		<tfoot>
			<tr>
				<td class="c-auswaehlen">
					<script type="text/javascript">
						// <![CDATA[
						document.write('<button onclick="toggle_selection(); return false;" class="auswahl-button"<?=L::accesskeyAttr(_("Auswahl umkehren [&O][login/nachrichten.php|3]"))?> tabindex="<?=$tabindex?>"><abbr title="<?=L::h(_("Auswahl umkehren [&O][login/nachrichten.php|3]"))?>"><?=L::h(_("A[uswahl umkehren]"))?></abbr></button>');
						// ]]>
					</script>
				</td>
				<td colspan="3"><input type="submit" name="delete" class="loeschen-button"<?=L::accesskeyAttr(_("Lösche&n[login/nachrichten.php|3]"))?> tabindex="<?=$tabindex+1?>" value="<?=L::h(_("Lösche&n[login/nachrichten.php|3]"), false)?>" /> <input type="submit" name="read" class="als-gelesen-markieren-button" tabindex="<?=$tabindex+2?>"<?=L::accesskeyAttr(_("Als gelesen markieren [&U][login/nachrichten.php|3]"))?> value="<?=L::h(_("Als gelesen markieren [&U][login/nachrichten.php|3]"), false)?>" /> <input type="submit" name="archive" class="archivieren-button"<?=L::accesskeyAttr(_("Archivieren&[login/nachrichten.php|3]"))?> tabindex="<?=$tabindex+3?>" value="<?=L::h(_("Archivieren&[login/nachrichten.php|3]"))?>" /></td>
			</tr>
		</tfoot>
	</table>
</form>
<?php
				$tabindex = $tabindex_save;
			}
		}
	}
	else
	{
		$ncount = array();
		$ges_ncount = array();
		foreach(array(Message::TYPE_KAEMPFE, Message::TYPE_SPIONAGE, Message::TYPE_TRANSPORT, Message::TYPE_SAMMELN, Message::TYPE_BESIEDELUNG, Message::TYPE_BENUTZERNACHRICHTEN, Message::TYPE_VERBUENDETE, Message::TYPE_POSTAUSGANG) as $i)
		{
			$unread = ($i == Message::TYPE_POSTAUSGANG ? 0 : Message::getUserMessages($me->getName(), $i, Message::STATUS_NEU));
			$total = Message::getUserMessagesCount($me->getName(), $i);
			$ncount[$i] = array($unread, $total, $unread > 0 ? "neu type-strong" : ($total > 0 ? "alt type-weak" : "leer type-empty"));
			$ges_ncount[0] += $unread;
			$ges_ncount[1] += $read;
		};

		if($ges_ncount[0] > 0)
			$ges_ncount[2] = 'neu type-strong';
		elseif($ges_ncount[1] > 0)
			$ges_ncount[2] = 'alt type-weak';
		else
			$ges_ncount[2] = 'leer type-empty';
?>
<h2><?=L::h(_("Nachrichten"))?></h2>
<ul class="nachrichten-neu-link possibilities">
	<li><a href="nachrichten.php?to=&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("&Neue Nachricht[login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("&Neue Nachricht[login/nachrichten.php|4]"))?></a></li>
</ul>
<dl class="nachrichten-kategorien categories">
	<dt class="c-kaempfe <?=$ncount[Message::TYPE_KAEMPFE][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_KAEMPFE)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("K&ämpfe[login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("K&ämpfe[login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-kaempfe <?=$ncount[Message::TYPE_KAEMPFE][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_KAEMPFE][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[1][1])?>)</span></dd>

	<dt class="c-spionage <?=$ncount[Message::TYPE_SPIONAGE][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_SPIONAGE)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Spi&onage[login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Spi&onage[login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-spionage <?=$ncount[Message::TYPE_SPIONAGE][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_SPIONAGE][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[2][1])?>)</span></dd>

	<dt class="c-transport <?=$ncount[Message::TYPE_TRANSPORT][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_TRANSPORT)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Transport [&J][login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Transport [&J][login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-transport <?=$ncount[Message::TYPE_TRANSPORT][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_TRANSPORT][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[3][1])?>)</span></dd>

	<dt class="c-sammeln <?=$ncount[Message::TYPE_SAMMELN][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_SAMMELN)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Sammeln [&Q][login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Sammeln [&Q][login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-sammeln <?=$ncount[Message::TYPE_SAMMELN][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_SAMMELN][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[4][1])?>)</span></dd>

	<dt class="c-besiedelung <?=$ncount[Message::TYPE_BESIEDELUNG][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_BESIEDELUNG)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Besiedel&ung[login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Besiedel&ung[login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-besiedelung <?=$ncount[Message::TYPE_BESIEDELUNG][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_BESIEDELUNG][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[5][1])?>)</span></dd>

	<dt class="c-benutzernachrichten <?=$ncount[Message::TYPE_BENUTZERNACHRICHTEN][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_BENUTZERNACHRICHTEN)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Benut&zernachrichten[login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Benut&zernachrichten[login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-benutzernachrichten <?=$ncount[Message::TYPE_BENUTZERNACHRICHTEN][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_BENUTZERNACHRICHTEN][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[6][1])?>)</span></dd>

	<dt class="c-verbeundete <?=$ncount[Message::TYPE_VERBUENDETE][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_VERBUENDETE)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Verb&ündete[login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Verb&ündete[login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-verbuendete <?=$ncount[Message::TYPE_VERBUENDETE][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_VERBUENDETE][0])?> <span class="gesamt">(<?=htmlspecialchars($ncount[7][1])?>)</span></dd>

	<dt class="c-postausgang foot <?=$ncount[Message::TYPE_POSTAUSGANG][2]?>"><a href="nachrichten.php?type=<?=htmlspecialchars(Message::TYPE_POSTAUSGANG)?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=L::accesskeyAttr(_("Postausgang [&W][login/nachrichten.php|4]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("Postausgang [&W][login/nachrichten.php|4]"))?></a></dt>
	<dd class="c-postausgang foot <?=$ncount[Message::TYPE_POSTAUSGANG][2]?>"><?=htmlspecialchars($ncount[Message::TYPE_POSTAUSGANG][1])?></dd>

	<dt class="c-gesamt foot <?=$ges_ncount[2]?>"><?=L::h(_("Gesamt"))?></dt>
	<dd class="c-gesamt foot <?=$ges_ncount[2]?>"><?=htmlspecialchars($ges_ncount[0])?> <span class="gesamt">(<?=htmlspecialchars($ges_ncount[1])?>)</span></dd>
</dl>
<?php
	}

	$gui->end();
?>
