<?php
	class SuaservConnection
	{
		protected static $connection = null;
		protected static $escape = array("[\r\n ]", "\$0");
		protected static $instances = 0;

		public function __construct()
		{
			$this->checkConnection(true);
			self::$instances++;
		}

		public function __destruct()
		{
			self::$instances--;
			if(self::$instances <= 0)
			{
				# Verbindung trennen
				fwrite(self::$connection, "quit\n");
				fclose(self::$connection);
				self::$connection = null;
			}
		}

		public function checkConnection($autoconnect=false)
		{
			if(!self::$connection)
			{
				if($autoconnect)
				{
					self::$connection = fsockopen(global_setting("SUASERV_HOST"), global_setting("SUASERV_PORT"));
					if(!self::$connection)
						throw new IOException("Could not connect to suaserv.");

					$server = fgets(self::$connection);
					if(!preg_match("/^suaserv (.*?)\n\$/", $server, $match))
						throw new IOException("This is not an suaserv.");
					if($match[1] != global_setting("SUASERV_VERSION"))
						throw new IOException("Wrong suaserv version.");

					$this->sendCommand("agent", "psua");
					$this->sendCommand("client", $_SERVER['REMOTE_ADDR']);
				}
				else
					throw new IOException("Not connected to suaserv.");
			}
		}

		public static function quote($param)
		{
			$param = preg_replace("/".str_replace("/", "\\/", self::$escape[0])."/", self::$escape[1], $param);
			return $param;
		}

		public function sendCommand()
		{
			$this->checkConnection();
			$parms = func_get_args();
			for($i=0; $i<count($parms); $i++)
				$parms[$i] = self::quote($parms[$i]);
			fwrite(self::$connection, implode(" ", $parms)."\n");

			$res = $this->parseResponseLine(fgets(self::$connection));
			$res[2] = null;
			if($res[0] == 0)
			{
				$data = fread(self::$connection, $res[1]);
				if(fgetc(self::$connection) != "\n")
					throw new IOException("Invalid data received.");
				$res[2] = $data;
			}
			return $res;
		}

		protected static function parseResponseLine($line)
		{
			if(!preg_match("/^(\\d+) (\\d+)\n\$/s", $line, $match))
				throw new InvalidArgumentException("Invalid response line.");

			return array($match[1], $match[2]);
		}
	}
?>
