<?php
	require('engine/include.php');

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

	gui::html_head();
?>
<h2><abbr title="Stars Under Attack" xml:lang="en">S-U-A</abbr> &ndash; Neuigkeiten</h2>
<?php
	$news_array = array();
	if(is_file(DB_NEWS) && filesize(DB_NEWS) > 0 && is_readable(DB_NEWS))
		$news_array = array_reverse(unserialize(gzuncompress(file_get_contents(DB_NEWS))));

	foreach($news_array as $news)
	{
		if(!is_array($news) || !isset($news['text']))
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
	<div class="time"><?=date('Y-m-d, H:i:s', $news['time'])?></div>
<?php
		}

		echo "\t<p>\n\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', utf8_htmlentities($news['text']))."\n\t</p>\n";
?>
</div>
<?php
	}

	gui::html_foot();
?>