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
	 * Benutzer können bestimmte Eingaben (zum Beispiel die Benutzerbeschreibung) formatieren, indem sie bestimmte HTML-Tags
	 * verwenden. Zeilenumbrüche in der Eingabe werden automatisch in Absätze konvertiert und die Korrektheit des HTML-Codes
	 * geprüft. Deshalb wird sowohl die Originaleingabe („Raw“) des Benutzers als auch die geparste und geprüfte Version („HTML“)
	 * gespeichert.
	*/

	class FormattedString
	{
		private $raw;
		private $html;

		/**
		 * @param string $raw
		*/

		function __construct($raw)
		{
			$this->raw = $raw;
			$this->html = self::parseHTML($this->raw);
		}

		/**
		 * Gibt die Raw-Eingabe zurück.
		 * @return string
		*/

		function getRawData()
		{
			return $this->raw;
		}

		/**
		 * Gibt die geparste HTML-Version zurück.
		 * @return string
		*/

		function getHTML()
		{
			return $this->html;
		}

		/**
		 * Entfernt ungueltiges HTML aus dem uebergebenen Code.
		 * Auf diese Weise kann sichergestellt werden, dass zum Beispiel in der Benutzerbeschreibung nur sauberes HTML ausgegeben wird.
		 * Ungueltige Elemente werden entfernt.
		 * @param string $string
		 * @return string
		 * @todo private machen, über all durch das Objekt statt die Funktion ersetzen
		*/

		static function parseHTML($string)
		{
			$root = self::parseHTML_getElementInformation('div');

			$remaining_string = str_replace("\t", " ", preg_replace("/\r\n|\r|\n/", "\n", $string));
			$string = '';
			$open_elements = array();
			while(($next_bracket = strpos($remaining_string, '<')) !== false)
			{
				if($next_bracket != 0)
				{
					$string .= htmlspecialchars(substr($remaining_string, 0, $next_bracket));
					$remaining_string = substr($remaining_string, $next_bracket);
				}

				if(substr($remaining_string, 1, 1) == '/')
				{
					if(!preg_match('/^<\\/([a-z]+) *>/', $remaining_string, $match) || count($open_elements) <= 0 || $open_elements[count($open_elements)-1] != strtolower($match[1]))
					{
						$string .= '&lt;';
						$remaining_string = substr($remaining_string, 1);
					}
					else
					{
						$string .= '</'.strtolower($match[1]).'>';
						$remaining_string = substr($remaining_string, strlen($match[0]));
						array_pop($open_elements);
					}
					continue;
				}

				if(!preg_match('/^<([a-z]+)( |>)/i', $remaining_string, $match) || ($close_bracket = strpos($remaining_string, '>')) === false)
				{
					$string .= '&lt;';
					$remaining_string = substr($remaining_string, 1);
					continue;
				}

				$element_name = strtolower($match[1]);
				$info = self::parseHTML_getElementInformation($element_name);
				if(!$info)
				{
					$string .= '&lt;';
					$remaining_string = substr($remaining_string, 1);
					continue;
				}
				if(count($open_elements))
					$parent_info = self::parseHTML_getElementInformation($open_elements[count($open_elements)-1]);
				else
					$parent_info = $root;

				if(!in_array($element_name, $parent_info[0]))
				{
					$string .= '&lt;';
					$remaining_string = substr($remaining_string, 1);
					continue;
				}

				$part = substr($remaining_string, 0, $close_bracket);
				$part = ' '.substr($part, strlen($element_name)+2);

				if($part != ' ' && !preg_match('/^( +(xml:)?[a-z]+="[^"]*")*( *\\/)?$/i', $part))
				{
					$string .= '&lt;';
					$remaining_string = substr($remaining_string, 1);
					continue;
				}

				$closed = (substr($part, -1) == '/');
				if($closed)
					$part = substr($part, 0, -1);
				else
					$open_elements[] = $element_name;

				preg_match_all('/ +([a-z:]+)="([^"]*)"/i', $part, $attrs, PREG_SET_ORDER);
				$attrs2 = array();
				foreach($attrs as $attr)
				{
					if(!isset($info[1][strtolower($attr[1])]))
						continue;
					$attrs2[] = strtolower($attr[1]).'="'.$attr[2].'"';
					unset($info[1][strtolower($attr[1])]);
				}

				if(in_array(true, $info[1]))
				{
					$string .= '&lt;';
					$remaining_string = substr($remaining_string, 1);
					continue;
				}

				array_unshift($attrs2, '<'.$element_name);
				$string .= implode(' ', $attrs2);
				if($closed)
					$string .= ' />';
				else
					$string .= '>';

				$remaining_string = substr($remaining_string, $close_bracket+1);
			}

			$string .= htmlspecialchars($remaining_string);

			$open_elements = array_reverse($open_elements);
			foreach($open_elements as $el)
				$string .= '</'.$el.'>';

			# Zeilenumbruchstruktur aufbauen
			$string = preg_replace("/> *(\r\n|\r|\n) *</", "><", $string);

			$remaining_string = $string;
			$string = '';
			$open_elements = array();
			$p_open = false;
			$span = self::parseHTML_getElementInformation('span');
			while(($next_bracket = strpos($remaining_string, '<')) !== false)
			{
				if($next_bracket != 0)
				{
					$part = substr($remaining_string, 0, $next_bracket);
					if(count($open_elements))
						$parent_info = self::parseHTML_getElementInformation($open_elements[count($open_elements)-1]);
					else
						$parent_info = $root;
					if(self::parseHTML_trim($part) != '' && in_array('span', $parent_info[0]))
					{
						if(!$p_open && count($open_elements) <= 0)
						{
							$string .= '<p>';
							$p_open = true;
						}
						if(in_array('br', $parent_info[0]))
						{
							if(count($open_elements) <= 0)
							{
								if(substr($part, -1) == "\n")
									$string .= preg_replace('/[\n]+/e', 'F::parseHTML_replNL(strlen(\'$0\'))', substr($part, 0, -1));
								else
									$string .= preg_replace('/[\n]+/e', 'F::parseHTML_replNL(strlen(\'$0\'))', $part);
							}
							else
							{
								if(substr($part, -1) == "\n")
									$string .= str_replace("\n", "<br />", substr($part, 0, -1));
								else
									$string .= str_replace("\n", "<br />", $part);
							}
						}
						else
							$string .= str_replace("\n", '', $part);
					}
					$remaining_string = substr($remaining_string, $next_bracket);
				}
				$close_bracket = strpos($remaining_string, '>');
				if(substr($remaining_string, 1, 1) == '/')
				{
					preg_match('/^<\\/([a-z]+) *>/', $remaining_string, $match);
					if(count($open_elements) > 0 && $open_elements[count($open_elements)-1] == $match[1])
						array_pop($open_elements);
				}
				elseif(preg_match('/^<([a-z]+)( |>)/', $remaining_string, $match))
				{
					if($p_open && !in_array($match[1], $span[0]))
					{
						$string .= "</p>\n";
						$p_open = false;
					}
					if(substr($remaining_string, $close_bracket-1, 1) != '/')
						$open_elements[] = $match[1];
				}

				$string .= substr($remaining_string, 0, $close_bracket+1);
				$remaining_string = substr($remaining_string, $close_bracket+1);
			}

			if(strlen($remaining_string) > 0 && trim($remaining_string) != '')
			{
				if(!$p_open)
				{
					$string .= '<p>';
					$p_open = true;
				}
				$string .= preg_replace('/[\n]+/e', 'F::parseHTML_replNL(strlen(\'$0\'))', $remaining_string);
			}
			if($p_open)
				$string .= '</p>';

			$string = preg_replace('/&amp;(#[0-9]{1,6};)/', '&$1', $string);
			$string = preg_replace('/&amp;(#x[0-9a-fA-F]{1,4};)/', '&$1', $string);
			$string = preg_replace('/&amp;([a-zA-Z0-9]{2,8};)/', '&$1', $string);

			$string = str_replace("\n<p></p>", '<br /><br />', $string);

			return $string;
		}

		/**
		 * Hilfsfunktion fuer parse_html(). Liefert ein Array mit Informationen zu einem HTML-Element zurueck:
		 * ( ( Erlaubtes Kind-Element ); ( Erlaubtes Attribut => Attribut erforderlich? ) )
		 * @param string $element
		 * @return array(string)
		*/

		private static function parseHTML_getElementInformation($element)
		{
			$elements = array(
				'div' => array('br div span table h4 h5 h6 a img em strong var code abbr acronym address blockquote cite dl dfn hr bdo ins kbd ul ol q samp var p', 'class title xml:lang dir datafld datasrc dataformates'),
				'span' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir datafld datasrc dataformates'),
				'table' => array('thead tbody tfoot', 'class title xml:lang dir summary'),
				'thead' => array('tr', 'class title xml:lang dir'),
				'tbody' => array('tr', 'class title xml:lang dir'),
				'tfoot' => array('tr', 'class title xml:lang dir'),
				'tr' => array('th td', 'class title xml:lang dir'),
				'td' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir abbr colspan rowspan'),
				'th' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir abbr colspan rowspan'),
				'caption' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'h4' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'h5' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'h6' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'a' => array('span img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir !href hreflang rel rev'),
				'img' => array('', 'class title xml:lang dir !src !alt longdesc'),
				'em' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'strong' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'var' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'code' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'abbr' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'acronym' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'address' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'blockquote' => array('div span table h4 h5 h6 a img em strong var code abbr acronym address blockquote cite dl dfn hr bdo ins kbd ul ol q samp var p', 'class title xml:lang dir cite'),
				'cite' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'dl' => array('dt dd', 'class title xml:lang dir'),
				'dt' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'dd' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'dfn' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'hr' => array('', 'class title xml:lang'),
				'bdo' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'ins' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir cite datetime'),
				'kbd' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'ul' => array('li', 'class title xml:lang dir'),
				'ol' => array('li', 'class title xml:lang dir'),
				'li' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'q' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir cite'),
				'samp' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'var' => array('span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir'),
				'p' => array('br span a img em strong var abbr acronym cite dfn bdo ins kbd q samp var', 'class title xml:lang dir datafld datasrc dataformates')
			);

			if(!isset($elements[$element]))
				return false;

			$return = array(explode(' ', $elements[$element][0]), array());
			$el_attrs = explode(' ', $elements[$element][1]);
			foreach($el_attrs as $el_attr)
			{
				if(substr($el_attr, 0, 1) == '!')
					$return[1][substr($el_attr, 1)] = true;
				else
					$return[1][$el_attr] = false;
			}
			return $return;
		}

		/**
		 * Hilfsfunktion fuer parse_html(). Ersetzt Zeilenumbrueche je nach Anzahl durch HTML-Absaetze oder -Zeilenumbrueche.
		 * @param string $string
		 * @param bool $minus1
		 * @return string
		*/

		private static function parseHTML_nls($string, $minus1)
		{
			$string2 = $string;
			$string = preg_replace('/[\n]+/e', 'repl_nl(strlen(\'$0\')-$minus1);', htmlspecialchars($player_info['description']));
			return $string;
		}

		/**
		 * Hilfsfunktion fuer parseHTML_nls(). Ersetzt einen String aus Zeilenumbruechen je nach deren Anzahl durch &lt;br /&gt; oder &lt;/p&gt;(&lt;br /&gt;)*&lt;p&gt;.
		 * @param int $len
		 * @return string
		*/

		private static function parseHTML_replNL($len)
		{
			if($len == 1)
				return "<br />";
			elseif($len == 2)
				return "</p>\n<p>";
			elseif($len > 2)
				return "</p>\n".str_repeat('<br />', $len-2)."\n<p>";
		}

		/**
		 * Hilfsfunktion fuer parse_html(). Wie trim(), entfernt jedoch nur Leerzeichen.
		 * @param string $string
		 * @return string
		*/

		private static function parseHTML_trim($string)
		{
			while(strlen($string) > 0 && $string[0] === ' ')
				$string = substr($string, 1);
			while(substr($string, -1) === ' ')
				$string = substr($string, 0, -1);
			return $string;
		}
	}