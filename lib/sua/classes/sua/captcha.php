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
	 * @author Candid Dauth
	 * @package sua
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Stellt statische Funktionen zur Verfügung, um Captchas zu implementieren.
	*/

	class Captcha
	{
		/**
		 * Gibt ein Formular aus, das den Benutzer auffordert, ein Bild abzutippen.
		 * @param int $tabindex Die Tabindex-Variable, wird innerhalb der Funktion weiter hochgezählt und die Tabindizes für die Formularfelder vergeben.
		 * @param int $tabs Mit wievielen Tabulatoren soll der Code eingerückt werden?
		 * @return void
		*/

		static function challenge(&$tabindex, $tabs=0)
		{
			$t = str_repeat("\t", $tabs);
			$options = array("tabindex" => $tabindex++, "lang" => _("[LANG]"));
			$url_prefix = (HTTPOutput::getProtocol() == "https" ? "https://api-secure" : "http://api");

			if(isset($_SERVER["REMOTE_ADDR"]) && !preg_match("/^\\d+\\.\\d+\\.\\d+\\.\\d+\$/", $_SERVER["REMOTE_ADDR"]))
			{
				/* IPv6-Workaround: Ein Bild wird über IPv4 geladen, das die IPv4-Adresse in die Session speichert, da recaptcha noch
				 * nicht mit IPv6 funktioniert. */
				try
				{
					$img_src = self::getConfig("ipv4");
?>
<?=$t?><img src="<?=htmlspecialchars(HTTPOutput::getProtocol()."://".$img_src)?>?session=<?=htmlspecialchars(urlencode(session_id()))?>" alt="" class="script" />
<?php
				}
				catch(CaptchaException $e){}
			}
?>
<?=$t?><form action="<?=htmlspecialchars($_SERVER["REQUEST_URI"])?>" method="post" class="captcha">
<?=$t?>	<script type="text/javascript">
<?=$t?>	// <![CDATA[
<?=$t?>		var RecaptchaOptions = <?=JS::aimplodeJS($options)?>;
<?=$t?>	// ]]>
<?=$t?>	</script>
<?=$t?>	<script type="text/javascript" src="<?=htmlspecialchars($url_prefix)?>.recaptcha.net/challenge?k=<?=htmlspecialchars(urlencode(self::getConfig("public")))?>"></script>
<?=$t?>	<noscript>
<?=$t?>		<iframe src="<?=htmlspecialchars($url_prefix)?>.recaptcha.net/noscript?k=<?=htmlspecialchars(urlencode(self::getConfig("public")))?>"></iframe><br>
<?=$t?>		<dl class="form">
<?=$t?>			<dt class="c-generierter-code"><label for="i-generierter-code"><?=L::h(_("Generierter Code"))?></label></dt>
<?=$t?>			<dd class="c-generierter-code"><textarea name="recaptcha_challenge_field" id="i-generierter-code" tabindex="<?=$tabindex++?>"></textarea></dd>
<?=$t?>		</dl>
<?=$t?>		<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"><?=L::h(_("Okay"))?></button><input type="hidden" name="recaptcha_response_field" value="manual_challenge"></button>
<?=$t?>	</noscript>
<?php
			F::makeHiddenFields($_POST, $tabs+1);
?>
<?=$t?></form>
<?php
		}

		/**
		 * Überprüft, ob die Eingaben, die der Benutzer ins Captcha::challenge()-Formular
		 * gemacht hat, stimmen. Wegen der IPv6-Inkompatibilität von recaptcha sollte
		 * $_SESSION["ipv4"] die IPv4-Adresse enthalten, sofern das Spiel über IPv6 aufgerufen werden kann.
		 * @param string $challenge Der Wert von $_POST["recaptcha_challenge_field"] nach Absenden des Formulars.
		 * @param string $response Der Wert von $_POST["recaptcha_response_field"] nach Absenden des Formulars.
		 * @return void
		 * @throw CaptchaException Wenn die Validierung fehlgeschlagen ist (unterschiedliche Fehlercodes, definiert als CaptchaExceptions::*_ERROR)
		*/

		static function validate($challenge, $response)
		{
			$fh = fsockopen("api-verify.recaptcha.net", 80, $errno, $errstr);
			if(!$fh)
				throw new CaptchaException($errno.": ".$errstr, CaptchaException::HTTP_ERROR);
			$request = array(
				"privatekey" => self::getConfig("private"),
				"remoteip" => isset($_SESSION["ipv4"]) ? $_SESSION["ipv4"] : $_SERVER["REMOTE_ADDR"],
				"challenge" => $challenge,
				"response" => $response
			);
			$query_string = JS::aimplode_url($request);

			fwrite($fh, "POST /verify HTTP/1.0\r\n");
			fwrite($fh, "Host: api-verify.recaptcha.net\r\n");
			fwrite($fh, "Content-type: application/x-www-form-urlencoded\r\n");
			fwrite($fh, "Content-length: ".strlen($query_string)."\r\n");
			fwrite($fh, "\r\n");
			fwrite($fh, $query_string."\r\n");

			$line = fgets($fh);
			$http = explode(" ", trim($line));
			if($http[1] != "200")
				throw new CaptchaException("Server sent HTTP status line: ".$line, CaptchaException::HTTP_ERROR);

			while(($line = fgets($fh)) !== "\r\n");
			$line1 = fgets($fh);
			if(trim($line1) != "true")
			{
				$line2 = fgets($fh);
				throw new CaptchaException(self::resolveErrorMessage(trim($line2)), CaptchaException::USER_ERROR);
			}
		}

		/**
		 * Erzeugt aus einem Fehlercode, der vom Recaptcha-Server gesandt wurde,
		 * eine lesbare Fehlermeldung.
		 * @param string $string Der Fehler-Code
		 * @return string
		*/

		static private function resolveErrorMessage($string)
		{
			$fh = fsockopen("api.recaptcha.net", 80, $errno, $errstr);
			if(!$fh)
				throw new CaptchaException($errno.": ".$errstr, CaptchaException::HTTP_ERROR);

			fwrite($fh, "GET /challenge?error=".urlencode($string)." HTTP/1.0\r\n");
			fwrite($fh, "Host: api.recaptcha.net\r\n");
			fwrite($fh, "\r\n");

			$line = fgets($fh);
			$http = explode(" ", trim($line));
			if($http[1] != "200")
				throw new CaptchaException("Server sent HTTP status line: ".$line, CaptchaException::HTTP_ERROR);

			while(($line = fgets($fh)) !== "\r\n");

			$content = "";
			while(!feof($fh))
				$content .= fread($fh, 1024);

			if(!preg_match("/document\\.write \\('(.*?[^\\\\])'\);/", $content, $m))
				throw new CaptchaException("Server sent unknown JavaScript code.", CaptchaException::HTTP_ERROR);

			return $message = preg_replace("/\\\\(.)/", "\$1", $m[1]);
		}

		/**
		 * Gibt entweder die ganze Konfiguration oder einen bestimmten Konfigurations-
		 * wert zurück.
		 * @param string|null $index Wenn angegeben, wird nur der übergebene Index der Konfigurationsdatei zurückgegeben.
		 * @return array(string)|string Liefert das Array mit der Konfiguration oder den gewünschten $index zurück.
		 * @throw CaptchaException Wenn keine oder eine fehlerhafte Konfiguration existiert.
		*/

		static function getConfig($index=null)
		{
			$config = Config::getLibConfig()->getConfig();
			if(!isset($config["captcha"]))
				throw new CaptchaException("Captchas are not configured.", CaptchaException::CONFIG_ERROR);
			$config = &$config["captcha"];

			if($index === null)
				return $config;
			if(!isset($config[$index]))
				throw new CaptchaException("Configuration setting ".$index." missing.", CaptchaException::CONFIG_ERROR);
			return $config[$index];
		}

		/**
		 * Versucht, alle für die Captcha-Implementierung notwendigen Konfigurationswerte auszulesen. Auf diese Weise kann Exceptions
		 * vorgebeugt werden.
		 * @throw CaptchaException {@see getConfig()}
		 * @return void
		*/

		static function prepareConfig()
		{
			self::getConfig("public");
			self::getConfig("private");
		}
	}
