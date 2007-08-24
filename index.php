<?php
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

	$SHOW_META_DESCRIPTION = true;
	home_gui::html_head();
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
	<h3><?=utf8_htmlentities($title)?><?=($author != '') ? ' <span class="author">('.utf8_htmlentities($author).')</span>' : ''?></h3>
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

	home_gui::html_foot();
?>
