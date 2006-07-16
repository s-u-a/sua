<?php
	$traffic_f = array(
		"Gi" => 1073741824,
		"G" => 1000000000,
		"Mi" => 1048576,
		"M" => 1000000,
		"Ki" => 1024,
		"k" => 1000
	);

	function fdelete($fh, $len, $bs=1024)
	{
		if($bs <= 0) return false;
		$pos = ftell($fh);
		while(true)
		{
			fseek($fh, $len, SEEK_CUR);
			$part = fread($fh, $bs);
			if($part === false) break;
			fseek($fh, -strlen($part)-$len, SEEK_CUR);
			fwrite($fh, $part);
			if(strlen($part) < $bs) break;
		}
		$ret = ftruncate($fh, ftell($fh));
		fseek($fh, $pos, SEEK_SET);
		return $ret;
	}

	header('Cache-control: max-age=1209600');
	header('Expires: '.strftime('%a, %d %b %Y %T %Z', time()+1209600));

	$image_path = urldecode($_SERVER['QUERY_STRING']);
	if($image_path == '' || !is_file($image_path) || strpos($image_path, '../') !== false)
		exit(1);

	if(substr($image_path, -4) == '.gif')
		header('Content-type: image/gif');
	elseif(substr($image_path, -4) == '.jpg' || substr($image_path, -5) == '.jpeg')
		header('Content-type: image/jpeg');
	elseif(substr($image_path, -4) == '.png')
		header('Content-type: image/png');
	else
		exit(1);

	$mirrors_db = "../../database.global/mirrors.state";

	$mirrors = array();
	$mirrors_ini = "../../database.global/mirrors";
	if(function_exists("parse_ini_file") && is_file($mirrors_ini) && is_readable($mirrors_ini))
	{
		$mirrors = parse_ini_file($mirrors_ini, true);
		foreach($mirrors as $k=>$v)
		{
			if(!isset($v['path'])) continue;

			$v['path'] = trim($v['path']);
			if(!$v['path']) continue;

			if(isset($v['traffic']))
			{
				$v['traffic'] = str_replace(",", ".", $v['traffic']);

				$factor = 1;
				foreach($traffic_f as $unit=>$f)
				{
					if(preg_match("/(\s*)".preg_quote($unit, "/")."(B?)$/i", $v['traffic']))
					{
						$factor = $f;
						break;
					}
				}
				$v['traffic'] = ((float) $v['traffic'])*$factor;
				if($v['traffic'] <= 0) $v['traffic'] = false;
			}
			else $v['traffic'] = false;

			$mirrors[$k] = $v;
		}

		if(count($mirrors) > 0)
		{
			if(!file_exists($mirrors_db)) touch($mirrors_db);
			$fh = fopen($mirrors_db, "r+");
			flock($fh, LOCK_EX);

			if(date("Y-m", filemtime($mirrors_db)) != date("Y-m"))
				ftruncate($fh, 0);

			$fsize = filesize($mirrors_db);

			$fpos = array();

			$now = 0;
			while($fsize > 0 && ($line = fgets($fh, $fsize)) !== false)
			{
				$line = explode("\t", trim($line));
				if(count($line) < 2 || !isset($mirrors[$line[0]]))
				{
					$now = ftell($fh);
					continue;
				}

				if($line[1] > $mirrors[$line[0]]['traffic'])
					unset($mirrors[$line[0]]);

				$fpos[$line[0]] = $now;
				$now = ftell($fh);
			}

			if(count($mirrors) > 0)
			{
				$mirror = array_rand($mirrors);
				$redirect = $mirrors[$mirror]['path']."?".$_SERVER['QUERY_STRING'];
				$filesize = filesize($image_path);

				$tr = 0;
				if(isset($fpos[$mirror]))
				{
					fseek($fh, $fpos[$mirror], SEEK_SET);
					$line = fgets($fh, $fsize+1);
					fseek($fh, $fpos[$mirror], SEEK_SET);
					fdelete($fh, strlen($line));
					$line = explode("\t", trim($line));
					$tr = $line[1];
				}
				$tr += $filesize;

				fseek($fh, 0, SEEK_END);
				fwrite($fh, $mirror."\t".$tr."\n");

				echo "Redirect: ".$redirect;
				#header("Location: ".$redirect, true, 307);
				exit(0);
			}
		}
	}

	echo "Readfile.";
	#readfile($image_path);
?>