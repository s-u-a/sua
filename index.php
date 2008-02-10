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

	function repl_nl($nls)
	{
		$len = strlen($nls);
		if($len == 1)
			return "<br />\n\t\t";
		elseif($len == 2)
			return "\n\t</p>\n\t<p>\n\t\t";
		elseif($len > 2)
			return "\n\t</p>\n".str_repeat('<br />', $len-2)."\n\t<p>\n\t\t";
	}

	$gui->setOption("meta", true);
	$gui->init();
?>
<h2><?=h(sprintf(_("%s – %s [s-u-a.net heading]"), _("[title_abbr]"), _("Neuigkeiten")))?></h2>
<?php
	$news_array = array();
	if(is_file(global_setting("DB_NEWS")) && filesize(global_setting("DB_NEWS")) > 0 && is_readable(global_setting("DB_NEWS")))
		$news_array = array_reverse(unserialize(gzuncompress(file_get_contents(global_setting("DB_NEWS")))));

	foreach($news_array as $news)
	{
		if(!is_array($news) || !isset($news['text_parsed']))
			continue;

		$title = 'Kein Titel';
		if(isset($news['title']) && trim($news['title']) != '')
			$title = trim($news['title']);

		$author = '';
		if(isset($news['author']) && trim($news['author']) != '')
			$author = trim($news['author']);
?>
<div class="news">
	<h3><?=htmlspecialchars($title)?><?=($author != '') ? ' <span class="author">('.htmlspecialchars($author).')</span>' : ''?></h3>
<?php
		if(isset($news['time']))
		{
?>
	<div class="time"><?=date(_('Y-m-d, H:i:s'), $news['time'])?></div>
<?php
		}

	print("\t".str_replace("\n", "\n\t", $news['text_parsed']));
?>
</div>
<?php
	}

	$gui->end();
?>
