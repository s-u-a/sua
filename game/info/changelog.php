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
	 * Zeigt das Changelog des Spiels an.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	$LOGIN_NOT_NEEDED = true;
	require('../include.php');

	$gui->init();

	$changelog = '';
	if(is_file(global_setting("DB_CHANGELOG")) && is_readable(global_setting("DB_CHANGELOG")))
		$changelog = file_get_contents(global_setting("DB_CHANGELOG"));

	$changelog = preg_split("/\r\n|\r|\n/", $changelog);
?>
<h2 id="changelog"><?=L::h(_("Changelog"))?></h2>
<ol class="changelog whole-page">
<?php
	foreach($changelog as $log)
	{
		$log = explode("\t", $log, 2);
		if(count($log) < 2)
		{
?>
	<li><?=htmlspecialchars($log[0])?></li>
<?php
		}
		else
		{
?>
	<li><span class="zeit"><?=date(_('Y-m-d, H:i:s'), $log[0])?>:</span> <?=htmlspecialchars($log[1])?></li>
<?php
		}
	}
?>
</ol>
<?php
	$gui->end();
?>