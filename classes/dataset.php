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

	abstract class Dataset
	{
		protected $datatype = 'dataset';
		protected $save_dir = false;
		protected $name = false;
		protected $filename = false;
		protected $changed = false;
		protected $status = false;
		protected $raw = false;
		protected $file_pointer = false;
		protected $cache = array();
		protected $location = false;
		protected $readonly = true;

		abstract function create();

		function __construct($name=false, $write=true)
		{
			if($name === false)
			{
				do $name = substr(md5(rand()), 0, 16); while(file_exists($this->save_dir.'/'.$name));
			}

			$this->readonly = !$write;

			if($this->save_dir === false)
				$this->status = 0;
			else
			{
				$this->name = $name;
				$this->filename = $this->save_dir.'/'.strtolower(urlencode($this->name));
				$this->location = $this->filename;
				if(!is_file($this->filename) || !is_readable($this->filename))
					$this->status = 0;
				else
				{
					if(!$write || !is_writeable($this->filename))
					{
						if($this->file_pointer = fopen($this->location, 'rb'))
						{
							$this->status = 2;
							Functions::fancyFlock($this->file_pointer, LOCK_SH);
						}
					}
					elseif(($this->file_pointer = fopen($this->location, 'r+b')) && Functions::fancyFlock($this->file_pointer, LOCK_EX))
						$this->status = 1;
					if($this->status)
						$this->read();
				}
			}
		}

		function __destruct()
		{
			if($this->status)
			{
				if($this->status == 1)
					$this->write();

				flock($this->file_pointer, LOCK_UN);
				fclose($this->file_pointer);

				$this->status = 0;
			}
		}

		function read($force=false)
		{
			if(!$this->status) return false;
			if($this->changed && !$force) $this->write();

			clearstatcache();
			$filesize = filesize($this->filename);
			fseek($this->file_pointer, 0, SEEK_SET);
			$this->raw = unserialize(bzdecompress(fread($this->file_pointer, $filesize)));
			$this->getDataFromRaw();
			return true;
		}

		function write($force=false, $getraw=true)
		{
			if(!$this->status && (!$force || file_exists($this->filename)))
				return false;
			if(!$this->changed && !$force) return 2;

			if($getraw)
				$this->getRawFromData();

			clearstatcache();
			if($force && !file_exists($this->filename))
			{
				if(!($this->file_pointer = fopen($this->location, 'a+')))
					return false;
				if(!Functions::fancyFlock($this->file_pointer, LOCK_EX))
					return false;
			}

			fseek($this->file_pointer, 0, SEEK_SET);
			$new_data = bzcompress(serialize($this->raw));

			$act_filesize = filesize($this->filename);
			$new_filesize = strlen($new_data);

			if($new_filesize > $act_filesize)
			{
				$diff = $new_filesize-$act_filesize;
				$fname = $this->filename;
				while(is_link($fname)) $fname = readlink($fname);
				$df = disk_free_space(dirname($fname));
				if($df < $diff)
				{
					echo "Error writing user array: No space left on disk.\n";
					exit(1);
				}
			}
			else ftruncate($this->file_pointer, $new_filesize);

			fwrite($this->file_pointer, $new_data);

			return true;
		}

		abstract protected function getDataFromRaw();
		abstract protected function getRawFromData();

		function getStatus()
		{
			return $this->status;
		}

		function getName()
		{
			return $this->name;
		}

		function readonly() { return $this->readonly; }
	}
