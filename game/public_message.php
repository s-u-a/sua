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
	 * Zeigt eine veröffentlichte Nachricht an.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage helpers
	*/

	namespace sua\homepage;

	require('engine.php');

	$databases = Config::get_databases();
	if(isset($_GET['database']) && isset($databases[$_GET['database']]))
		define_globals($_GET['database']);

	$gui = Classes::LoginGui();
	if(!isset($_GET['database']) || !isset($databases[$_GET['database']]) || !isset($_GET['id']) || !PublicMessage::publicMessageExists($_GET['id']))
		$gui->fatal(h(_("Die gewünschte Nachricht existiert nicht.")));

	$gui->init();
	$message = Classes::PublicMessage($_GET['id']);
?>
<dl class="nachricht-informationen type-<?=htmlspecialchars($message->type())?><?=$message->html() ? ' html' : ''?>">
<?php
	if($message->from() != '')
	{
?>
	<dt class="c-absender"><?=L::h(_("Absender"))?></dt>
	<dd class="c-absender"><?=htmlspecialchars($message->from())?></dd>
<?php
	}
?>
	<dt class="c-empfaenger"><?=L::h(_("Empfänger"))?></dt>
	<dd class="c-empfaenger"><?=htmlspecialchars($message->to())?></dd>

	<dt class="c-betreff"><?=L::h(_("Betreff"))?></dt>
	<dd class="c-betreff"><?=htmlspecialchars($message->subject())?></dd>

	<dt class="c-zeit"><?=L::h(_("Zeit"))?></dt>
	<dd class="c-zeit"><?=date(_('H:i:s, Y-m-d'), $message->time())?></dd>

	<dt class="c-nachricht"><?=L::h(_("Nachricht"))?></dt>
	<dd class="c-nachricht">
<?php
		print("\t\t\t\t".preg_replace("/\r\n|\r|\n/", "\n\t\t\t\t", $message->text()));
?>
	</dd>
</dl>
<?php
	$gui->end();
?>