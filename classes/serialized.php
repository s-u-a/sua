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
	abstract class Serialized implements Singleton,Dataset
	{
		protected static $save_dir = false;
		protected $name = false;
		protected $filename = false;
		protected $changed = false;
		protected $raw = false;
		protected $file_pointer = false;
		protected $cache = array();
		protected $location = false;

		function getName()
		{
			return $this->name;
		}

		static function datasetName($name=null)
		{
			if(!isset($name))
			{
				do $name = Functions::randomID(); while(file_exists(static::$save_dir.'/'.$name));
			}

			$name = preg_replace("/[\x00-\x7f]/e", "strtolower('$0')", $name);
		}

		static function nameToFilename($name)
		{
			return static::$save_dir."/".strtolower(urlencode($name));
		}

		static function filenameToName($fname)
		{
			return urldecode(basename($fname));
		}

		static function getList()
		{
			$list = array();
			$dir = dir(static::$save_dir);
			while(($fname = $dir->read()) !== false)
			{
				if(!is_file(static::$save_dir."/".$fname))
					continue;
				$list[] = static::filenameToName($fname);
			}
			return $list;
		}

		static function exists($name)
		{
			return file_exists(static::nameToFilename($name));
		}

		function __construct($name)
		{
			$this->name = self::datasetName($name);
			$this->filename = static::nameToFilename(static::datasetName($this->name));
			$this->location = $this->filename;
			if(!is_file($this->filename))
				throw new SerializedException("File does not exist.");
			else
			{
				$this->file_pointer = fopen($this->location, 'r+b'));
				if(!$this->file_pointer || !Functions::fancyFlock($this->file_pointer, LOCK_EX))
					throw new SerializedException("File is not openable.");
				$this->read();
			}
		}

		function __destruct()
		{
			$this->write();

			flock($this->file_pointer, LOCK_UN);
			fclose($this->file_pointer);
		}

		static function encode($raw)
		{
			return bzcompress(serialize($raw));
		}

		static function decode($data)
		{
			return unserialize(bzdecompress($data));
		}

		function read($force=false)
		{
			if($this->changed && !$force) $this->write();

			clearstatcache();
			$filesize = filesize($this->filename);
			fseek($this->file_pointer, 0, SEEK_SET);
			$this->raw = self::decode(fread($this->file_pointer, $filesize));
			$this->getDataFromRaw();
			return true;
		}

		function write($force=false, $getraw=true)
		{
			if(!$this->changed && !$force) return 2;

			if($getraw)
				$this->getRawFromData();

			self::store($this->getName(), $this->raw, $this->file_pointer);

			return true;
		}

		static protected function store($name, $data, $fh=null)
		{
			clearstatcache();

			$name = self::datasetName($name);
			$fname = self::nameToFilename($name);
			$do_unlock = !isset($fh);
			if($do_unlock)
			{
				$fh = fopen($fname, "a+");
				if(!$fh || !Functions::fancyFlock($fh, LOCK_EX))
					throw new SerializedExcepion("Could not write to file.");
			}

			fseek($fh, 0, SEEK_SET);
			$new_data = self::encode($data);

			$act_filesize = filesize($fh);
			$new_filesize = strlen($new_data);

			if($new_filesize > $act_filesize)
			{
				$diff = $new_filesize-$act_filesize;
				while(is_link($fname)) $fname = readlink($fname);
				$df = disk_free_space(dirname($fname));
				if($df < $diff)
					throw new SerializedException("No space left on disk.");
			}
			else ftruncate($fh, $new_filesize);

			fwrite($fh, $new_data);

			if($do_unlock)
				flock($fh, LOCK_UN);
		}

		abstract protected function getDataFromRaw();
		abstract protected function getRawFromData();
	}
