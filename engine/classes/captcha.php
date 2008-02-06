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
	class Captcha
	{
		protected static $languages = array(
			"en_GB" => "en",
			"en_US" => "en",
			"de_DE" => "de"
		);

		public static $FIELDS = array("recaptcha_challenge_field", "recaptcha_response_field");

		static function challenge(&$tabindex, $tabs=0)
		{
			$t = str_repeat("\t", $tabs);
			$options = array("tabindex" => $tabindex++);
			if(isset(self::$languages[language()]))
				$options["lang"] = self::$languages[language()];
			$url_prefix = (global_setting("PROTOCOL") == "https" ? "https://api-secure" : "http://api");
?>
<?=$t?><form action="<?=htmlspecialchars($_SERVER["REQUEST_URI"])?>" method="post" class="captcha">
<?=$t?>	<script type="text/javascript">
<?=$t?>	// <![CDATA[
<?=$t?>		var RecaptchaOptions = <?=aimplode_js($options)?>;
<?=$t?>	// ]]>
<?=$t?>	</script>

<?=$t?>	<script type="text/javascript" src="<?=htmlspecialchars($url_prefix)?>.recaptcha.net/challenge?k=<?=htmlspecialchars(urlencode(self::getConfig("public")))?>"></script>
<?=$t?>	<noscript>
<?=$t?>		<iframe src="<?=htmlspecialchars($url_prefix)?>.recaptcha.net/noscript?k=<?=htmlspecialchars(urlencode(self::getConfig("public")))?>"></iframe><br>
<?=$t?>		<dl class="form">
<?=$t?>			<dt class="c-generierter-code"><label for="i-generierter-code"><?=h(_("Generierter Code"))?></label></dt>
<?=$t?>			<dd class="c-generierter-code"><textarea name="recaptcha_challenge_field" id="i-generierter-code" tabindex="<?=$tabindex++?>"></textarea></dd>
<?=$t?>		</dl>
<?=$t?>		<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"><?=h(_("Okay"))?></button><input type="hidden" name="recaptcha_response_field" value="manual_challenge"></button>
<?=$t?>	</noscript>
<?php
			make_hidden_fields($_POST, $tabs+1);
?>
<?=$t?></form>
<?php
		}

		static function validate($challenge, $response)
		{
			$fh = fsockopen("api-verify.recaptcha.net", 80, $errno, $errstr);
			if(!$fh)
				throw new CaptchaException($errno.": ".$errstr, CaptchaException::$HTTP_ERROR);
			$request = array(
				"privatekey" => self::getConfig("private"),
				"remoteip" => $_SERVER["REMOTE_ADDR"],
				"challenge" => $challenge,
				"response" => $response
			);
			$query_string = aimplode_url($request);

			fwrite($fh, "POST /verify HTTP/1.0\r\n");
			fwrite($fh, "Host: api-verify.recaptcha.net\r\n");
			fwrite($fh, "Content-type: application/x-www-form-urlencoded\r\n");
			fwrite($fh, "Content-length: ".strlen($query_string)."\r\n");
			fwrite($fh, "\r\n");
			fwrite($fh, $query_string."\r\n");

			$line = fgets($fh);
			$http = explode(" ", trim($line));
			if($http[1] != "200")
				throw new CaptchaException("Server sent HTTP status line: ".$line, CaptchaException::$HTTP_ERROR);

			while(($line = fgets($fh)) !== "\r\n");
			$line1 = fgets($fh);
			if(trim($line1) != "true")
			{
				$line2 = fgets($fh);
				throw new CaptchaException(self::resolveErrorMessage(trim($line2)), CaptchaException::$USER_ERROR);
			}
		}

		static function resolveErrorMessage($string)
		{
			$fh = fsockopen("api.recaptcha.net", 80, $errno, $errstr);
			if(!$fh)
				throw new CaptchaException($errno.": ".$errstr, CaptchaException::$HTTP_ERROR);

			fwrite($fh, "GET /challenge?error=".urlencode($string)." HTTP/1.0\r\n");
			fwrite($fh, "Host: api.recaptcha.net\r\n");
			fwrite($fh, "\r\n");

			$line = fgets($fh);
			$http = explode(" ", trim($line));
			if($http[1] != "200")
				throw new CaptchaException("Server sent HTTP status line: ".$line, CaptchaException::$HTTP_ERROR);

			while(($line = fgets($fh)) !== "\r\n");

			$content = "";
			while(!feof($fh))
				$content .= fread($fh, 1024);

			if(!preg_match("/document\\.write \\('(.*?[^\\\\])'\);/", $content, $m))
				throw new CaptchaException("Server sent unknown JavaScript code.", CaptchaException::$HTTP_ERROR);

			return $message = preg_replace("/\\\\(.)/", "\$1", $m[1]);
		}

		static function getConfig($index=null)
		{
			static $config;
			if(!isset($config))
			{
				if(!is_file(global_setting("DB_CAPTCHA")) || !is_readable(global_setting("DB_CAPTCHA")))
					throw new CaptchaException(global_setting("DB_CAPTCHA")." not readable.", CaptchaException::$CONFIG_ERROR);
				$config = parse_ini_file(global_setting("DB_CAPTCHA"));
				if(!$config)
				{
					$config = null;
					throw new CaptchaException("Configuration error.");
				}
			}

			if($index === null)
				return $config;
			if(!isset($config[$index]))
				throw new CaptchaException("Configuration setting ".$index." missing.", CaptchaException::$CONFIG_ERROR);
			return $config[$index];
		}
	}

	class CaptchaException extends SuaException
	{
		static $HTTP_ERROR = 1;
		static $CONFIG_ERROR = 2;
		static $USER_ERROR = 3;

		function __construct($message = null, $code = 0)
		{
			parent::__construct($message, $code);
		}
	}