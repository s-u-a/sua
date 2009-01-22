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
	 * Stellt statische Funktionen zur Verfügung, um mit GPG zu arbeiten, zum Beispiel
	 * zum Signieren oder Verschlüsseln von Text.
	*/

	class GPG
	{
		/**
		 * Oeffnet den GPG-Schluessel entsprechend der Konfiguration und liefert bei Erfolg ein gnupg-Object zurueck.
		 * @param bool $return_public_key Wenn true, wird statt der gnupg-Instanz der öffentliche Schlüssel zurückgeliefert.
		 * @return gnupg|string Eine gnupg-Instanz oder der öffentliche Schlüssel.
		 * @throw GPGException Wenn keine Konfiguration vorliegt oder der Schlüssel nicht geöffnet werden konnte.
		*/

		static function init($return_public_key=false)
		{
			static $gpg;

			$config = Config::getConfig();
			if(!isset($config["gpg"]))
				throw new GPGException("GPG is not configured.");
			$config = & $config["gpg"];

			if(!isset($gpg))
			{
				if(!class_exists("gnupg"))
					throw new GPGException("The PHP gnupg extension (PECL) is not installed.");
				$gpg = new gnupg();
				$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
				$gpg->setsignmode(gnupg::SIG_MODE_CLEAR);
				if(isset($config["gpghome"]))
					putenv("GNUPGHOME=".$config["gpghome"]);
				if(!$gpg->addsignkey($config["fingerprint"]) || !$gpg->adddecryptkey($config["fingerprint"], ""))
					throw new GPGException("Could not open the key.");
			}
			if($return_public_key)
				return $gpg->export($config["fingerprint"]);
			else
				return $gpg;
		}

		/**
		 * Signiert den gegebenen Text wenn moeglich per GPG.
		*/

		static function sign($text)
		{
			$gpg = self::init();
			if(!$gpg)
				return $text;
			$return = $gpg->sign($text);
			if($return === false)
				return $text;
			return $return;
		}

		/**
		 * Signiert den Text, gibt aber nur die Signatur, ohne Header, zurück.
		 * @param string $text
		 * @return string
		*/

		static function smallsign($text)
		{
			$gpg = self::init();

			$signed = $gpg->sign($text);
			if(!preg_match("/(^|\n)-----BEGIN PGP SIGNATURE-----\r?\n.*?\r?\n\r?\n(.*?)\r?\n-----END PGP SIGNATURE-----(\r?\n|\$)/s", $signed, $m))
				throw new GPGException("Unrecognised output.");
			return $m[2];
		}

		/**
		 * Signiert und verschluesselt den gegebenen Text wenn moeglich per GPG.
		 * @param string $text
		 * @param string $fingerprint Der Fingerprint des Schlüssels, der den verschlüsselten Text dekodieren können soll.
		 * @param bool $return_text_on_failure Liefert bei Fehlschlag den Text unverschlüsselt zurück.
		 * @return string
		*/

		static function encrypt($text, $fingerprint, $return_text_on_failure=true)
		{
			try
			{
				$gpg = self::init();
			}
			catch(GPGException $e)
			{
				return $text;
			}
			$gpg->addencryptkey($fingerprint);
			$encrypted = $gpg->encryptsign($text);
			$gpg->clearencryptkeys();
			if($encrypted === false)
			{
				if($return_text_on_failure)
					return $text;
				else
					throw new GPGException("Encrypting failed.");
			}
			return $encrypted;
		}

		/**
		 * Verschlüsselt und signiert $text für $fingerprint, gibt aber nur den verschlüsselten Text ohne Header zurück.
		 * @param string $text
		 * @param string $fingerprint Fingerprint, dessen Schlüssel den Text dekodieren können soll
		 * @return string
		*/

		static function smallencrypt($text, $fingerprint)
		{
			$signed = self::encrypt($text, $fingerprint, false);
			if(!preg_match("/(^|\n)-----BEGIN PGP MESSAGE-----\r?\n.*?\r?\n\r?\n(.*?)\r?\n-----END PGP MESSAGE-----(\r?\n|\$)/s", $signed, $m))
				throw new GPGException("Unrecognised output.");
			return $m[2];
		}

		/**
		 * Entschlüsselt den Text.
		 * @param string $text Der verschlüsselte Text.
		 * @return string
		*/

		static function decrypt($text)
		{
			$gpg = self::init();
			return $gpg->decrypt($text);
		}
	}