<?php
	class Galaxy
	{
		private $status = false;
		private $file_pointer = false;
		private $cache = array();
		private $filesize = false;
		private $filename = false;
		private $galaxy = false;
		
		function __construct($galaxy)
		{
			$this->filename = DB_UNIVERSE.'/'.$galaxy;
			$this->galaxy = $galaxy;
			if(is_file($this->filename) && is_readable($this->filename))
			{
				$this->filesize = $filesize = filesize($this->filename);
				if(is_writeable($this->filename))
				{
					$this->file_pointer = fopen($this->filename, 'r+');
					flock($this->file_pointer, LOCK_EX);
					$this->status = 1;
				}
				else
				{
					$this->file_pointer = fopen($this->filename, 'r');
					flock($this->file_pointer, LOCK_SH);
					$this->status = 2;
				}
			}
		}
		
		function __destruct()
		{
			if($this->status)
			{
				flock($this->file_pointer, LOCK_UN);
				fclose($this->file_pointer);
				$this->status = false;
			}
		}
		
		function getName() # For Instances
		{
			return $this->galaxy;
		}
		
		function getStatus()
		{
			return $this->status;
		}
		
		function getSystemsCount()
		{
			if(!$this->status) return false;
			return 999;
		}
		
		private function seekSystem($system)
		{
			if(!$this->status) return false;
			
			$system = (int) $system;
			if($system < 0) return false;
			
			$pos = ($system-1)*1655;
			if($this->filesize < $pos+1655) return false; # System existiert nicht
			
			fseek($this->file_pointer, $pos, SEEK_SET);
			return true;
		}
		
		function getPlanetsCount($system)
		{
			if(!$this->status) return false;
			
			$system = (int) $system;
			
			if(!isset($this->cache['getPlanetsCount'])) $this->cache['getPlanetsCount'] = array();
			if(!isset($this->cache['getPlanetsCount'][$system]))
			{
				if(!$this->seekSystem($system)) return false;
				$this->cache['getPlanetsCount'][$system] = (ord(fread($this->file_pointer, 1))>>3)+10;
			}
			return $this->cache['getPlanetsCount'][$system];
		}
		
		function getPlanetOwner($system, $planet)
		{
			if(!$this->status) return false;
			
			$planet = (int) $planet;
			$system = (int) $system;
			
			if(!isset($this->cache['getPlanetOwner'])) $this->cache['getPlanetOwner'] = array();
			if(!isset($this->cache['getPlanetOwner'][$system])) $this->cache['getPlanetOwner'][$system] = array();
			if(!isset($this->cache['getPlanetOwner'][$system][$planet]))
			{
				$planets_count = $this->getPlanetsCount($system);
				if(!$planets_count) return false;
				if($planet > $planets_count) return false;
				
				if(!$this->seekSystem($system)) return false;
				
				fseek($this->file_pointer, 35+($planet-1)*24, SEEK_CUR);
				$this->cache['getPlanetOwner'][$system][$planet] = trim(fread($this->file_pointer, 24));
			}
			return $this->cache['getPlanetOwner'][$system][$planet];
		}
		
		function setPlanetOwner($system, $planet, $owner)
		{
			if($this->status != 1) return false;
			
			$system = (int) $system;
			$planet = (int) $planet;
			$owner = trim(substr($owner, 0, 24));
			
			$planets_count = $this->getPlanetsCount($system);
			if(!$planets_count || $planet > $planets_count) return false;
			
			if(!$this->seekSystem($system)) return false;
			
			fseek($this->file_pointer, 35+($planet-1)*24, SEEK_CUR);
			if(!fwrite($this->file_pointer, $owner)) return false;
			if(strlen($owner) < 24) fwrite($this->file_pointer, str_repeat(' ', 24-strlen($owner)));
			
			if(!isset($this->cache['getPlanetOwner'])) $this->cache['getPlanetOwner'] = array();
			if(!isset($this->cache['getPlanetOwner'][$system])) $this->cache['getPlanetOwner'][$system] = array();
			$this->cache['getPlanetOwner'][$system][$planet] = $owner;
			return true;
		}
		
		function getPlanetName($system, $planet)
		{
			if(!$this->status) return false;
			
			$planet = (int) $planet;
			$system = (int) $system;
			
			if(!isset($this->cache['getPlanetName'])) $this->cache['getPlanetName'] = array();
			if(!isset($this->cache['getPlanetName'][$system])) $this->cache['getPlanetName'][$system] = array();
			if(!isset($this->cache['getPlanetName'][$system][$planet]))
			{
				$planets_count = $this->getPlanetsCount($system);
				if(!$planets_count) return false;
				if($planet > $planets_count) return false;
				
				if(!$this->seekSystem($system)) return false;
				
				fseek($this->file_pointer, 755+($planet-1)*24, SEEK_CUR);
				$this->cache['getPlanetName'][$system][$planet] = trim(fread($this->file_pointer, 24));
			}
			return $this->cache['getPlanetName'][$system][$planet];
		}
		
		function setPlanetName($system, $planet, $name)
		{
			if($this->status != 1) return false;
			
			$system = (int) $system;
			$planet = (int) $planet;
			$name = trim(substr($name, 0, 24));
			
			$planets_count = $this->getPlanetsCount($system);
			if(!$planets_count || $planet > $planets_count) return false;
			
			if(!$this->seekSystem($system)) return false;
			
			fseek($this->file_pointer, 755+($planet-1)*24, SEEK_CUR);
			if(!fwrite($this->file_pointer, $name)) return false;
			if(strlen($name) < 24) fwrite($this->file_pointer, str_repeat(' ', 24-strlen($name)));
			
			if(!isset($this->cache['getPlanetName'])) $this->cache['getPlanetName'] = array();
			if(!isset($this->cache['getPlanetName'][$system])) $this->cache['getPlanetName'][$system] = array();
			$this->cache['getPlanetName'][$system][$planet] = $name;
			return true;
		}
		
		function getPlanetOwnerAlliance($system, $planet)
		{
			if(!$this->status) return false;
			
			$planet = (int) $planet;
			$system = (int) $system;
			
			if(!isset($this->cache['getPlanetOwnerAlliance'])) $this->cache['getPlanetOwnerAlliance'] = array();
			if(!isset($this->cache['getPlanetOwnerAlliance'][$system])) $this->cache['getPlanetOwnerAlliance'][$system] = array();
			if(!isset($this->cache['getPlanetOwnerAlliance'][$system][$planet]))
			{
				$planets_count = $this->getPlanetsCount($system);
				if(!$planets_count) return false;
				if($planet > $planets_count) return false;
				
				if(!$this->seekSystem($system)) return false;
				
				fseek($this->file_pointer, 1475+($planet-1)*6, SEEK_CUR);
				$this->cache['getPlanetOwnerAlliance'][$system][$planet] = trim(fread($this->file_pointer, 6));
			}
			return $this->cache['getPlanetOwnerAlliance'][$system][$planet];
		}
		
		function setPlanetOwnerAlliance($system, $planet, $alliance)
		{
			if($this->status != 1) return false;
			
			$system = (int) $system;
			$planet = (int) $planet;
			$alliance = trim(substr($alliance, 0, 6));
			
			$planets_count = $this->getPlanetsCount($system);
			if(!$planets_count || $planet > $planets_count) return false;
			
			if(!$this->seekSystem($system)) return false;
			
			fseek($this->file_pointer, 1475+($planet-1)*6, SEEK_CUR);
			if(!fwrite($this->file_pointer, $name)) return false;
			if(strlen($name) < 6) fwrite($this->file_pointer, str_repeat(' ', 6-strlen($name)));
			
			if(!isset($this->cache['getPlanetOwnerAlliance'])) $this->cache['getPlanetOwnerAlliance'] = array();
			if(!isset($this->cache['getPlanetOwnerAlliance'][$system])) $this->cache['getPlanetOwnerAlliance'][$system] = array();
			$this->cache['getPlanetOwnerAlliance'][$system][$planet] = $name;
			return true;
		}
		
		function getPlanetSize($system, $planet)
		{
			if(!$this->status) return false;
			
			$planet = (int) $planet;
			$system = (int) $system;
			
			if(!isset($this->cache['getPlanetSize'])) $this->cache['getPlanetSize'] = array();
			if(!isset($this->cache['getPlanetSize'][$system])) $this->cache['getPlanetSize'][$system] = array();
			if(!isset($this->cache['getPlanetSize'][$system][$planet]))
			{
				$planets_count = $this->getPlanetsCount($system);
				if(!$planets_count) return false;
				if($planet > $planets_count) return false;
				
				if(!$this->seekSystem($system)) return false;
				
				$bit_position = 5+($planet-1)*9;
				$byte_position = $bit_position%8;
				fseek($this->file_pointer, $bit_position-$byte_position, SEEK_CUR);
				$bytes = (ord(fread($this->file_pointer, 1)) << 8) & ord(fread($this->file_pointer, 1));
				$bytes = $bytes & ((1 << (17-$byte_position))-1);
				$bytes = $bytes >> (7-$byte_position);
				$this->cache['getPlanetSize'][$system][$planet] = $bytes;
			}
			return $this->cache['getPlanetSize'][$system][$planet];
		}
		
		function setPlanetSize($system, $planet, $size)
		{
			if($this->status != 1) return false;
			
			$system = (int) $system;
			$planet = (int) $planet;
			$size = (int) $size;
			if($size < 100 || $size > 500) return false;
			$size -= 100;
			
			$planets_count = $this->getPlanetsCount($system);
			if(!$planets_count) return false;
			if($planet > $planets_count) return false;
			
			if(!$this->seekSystem($system)) return false;
			
			$bit_position = 5+($planet-1)*9;
			$byte_position = $bit_position%8;
			fseek($this->file_pointer, $bit_position-$byte_position, SEEK_CUR);
			
			$byte1 = ord(fread($this->file_pointer, 1));
			$byte1 -= $byte1%(1<<(8-$byte_position));
			$byte1 = $byte1 | ($size>>$byte_position);
			
			$byte2 = ord(fread($this->file_pointer, 1));
			$byte2 = $byte2 & ((1<<(6-$byte_position))-1);
			$byte2 = $byte2 | ($size - ($size%(1<<$byte_position)));
			
			fseek($this->file_pointer, -2, SEEK_CUR);
			if(!fwrite($this->file_pointer, chr($byte1).chr($byte2))) return false;
			
			if(!isset($this->cache['getPlanetSize'])) $this->cache['getPlanetSize'] = array();
			if(!isset($this->cache['getPlanetSize'][$system])) $this->cache['getPlanetSize'][$system] = array();
			$this->cache['getPlanetSize'][$system][$planet] = $this->cache['getPlanetSize'][$system][$planet] = array();
		}
		
		function getPlanetClass($system, $planet)
		{
			if(!$this->status) return false;
			
			$galaxy = $this->galaxy;
			$type = (((floor($system/100)+1)*(floor(($system%100)/10)+1)*(($system%10)+1))%$planet)*$planet+($system%(($galaxy+1)*$planet));
			return $type%20+1;
		}
		
		function resetPlanet($system, $planet)
		{
			if(!$this->status) return false;
			
			return ($this->setPlanetName($system, $planet, '') && $this->setPlanetOwner($system, $planet, '')
			&& $this->setPlanetOwnerAlliance($system, $planet, '') && $this->setPlanetSize(rand(100, 500)));
		}
	}
	
	function getGalaxiesCount()
	{
		for($i=0; is_file(DB_UNIVERSE.'/'.$i) && is_readable(DB_UNIVERSE.'/'.$i); $i++);
		return $i;
	}
?>