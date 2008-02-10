////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/// S-U-A JavaScripts, hoechste Praezision und hoechster Komfort bei hoechster CPU-Last, Performance-Stufe 3 ///
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

/////////////////////////////
/// Allgemeine Funktionen ///
/////////////////////////////

function set_time_globals(server_time)
{ // Initialisiert Anfangswerte
	// Unterschied zwischen Lokalzeit und Serverzeit feststellen
	window.local_time_obj = new Date();
	window.local_time = Math.round(local_time_obj.getTime() / 1000);
	window.time_diff = local_time-server_time;

	// Array fuer die Countdowns, die spaeter jede Sekunde abgearbeitet werden
	window.countdowns = new Array();
}

function mk2(string)
{ // Haengt eine 0 vorne an eine Zahl, sodass diese zweistellig wird
	string = ''+string;
	while(string.length < 2)
		string = '0'+string;

	return string;
}

function ths(old_count)
{ // Fuegt Tausendertrennzeichen in eine Zahl ein
	var minus = false;
	if(old_count < 0)
	{
		old_count *= -1;
		minus = true;
	}
	var count = new String(Math.floor(old_count));
	var new_count = new Array();
	var first_letters = count.length%3;
	if(first_letters == 0)
		first_letters = 3;
	new_count.push(count.substr(0, first_letters));
	var max_i = (count.length-first_letters)/3;
	for(var i=0; i<max_i; i++)
		new_count.push(count.substr(i*3+first_letters, 3));
	new_count = new_count.join(ths_utf8);
	if(minus)
		new_count = "\u2212"+new_count;
	return new_count;
}

function myParseInt(string)
{
	var count = parseInt(string);
	if(isNaN(count) || count < 0)
		return 0;
	else
		return count;
}

function seconds2string(seconds)
{ // Macht aus einer Sekundendauer eine lesbare Zeitspanne
	if(seconds < 0) seconds = 0;

	var timestring = '';
	if(seconds >= 86400)
	{
		timestring += Math.floor(seconds/86400)+'\u2009d ';
		seconds = seconds % 86400;
	}
	timestring += mk2(Math.floor(seconds/3600))+':'+mk2(Math.floor((seconds%3600)/60))+':'+mk2(Math.floor(seconds%60));
	return timestring;
}

function getCookies()
{
	var c = { };
	if(document.cookie)
	{
		var c_split = document.cookie.split(/;/);
		for(var i=0; i<c_split.length; i++)
		{
			c_split_i = c_split[i].split(/=/);
			if(c_split_i < 2) continue;
			c[decodeURIComponent(c_split_i[0])] = decodeURIComponent(c_split_i[1]);
		}
	}
	return c;
}

//////////////////
/// Countdowns ///
//////////////////

function init_countdown(obj_id, f_time, show_cancel, sleep_seconds, finish_url, finish_callback)
{ // Initialisiert einen Countdown
	// obj_id: ID des Items, HTML-ID: restbauzeit-[id]
	// f_time: Fertigstellungs-Serverzeit

	if(slow_terminal) return false;

	// Abbruchbutton anzeigen
	show_cancel = ((show_cancel == null) ? true : (show_cancel == true));

	// Sekunden, die bei 0:00 zu warten sind, bevor Fertig angezeigt wird
	sleep_seconds = (sleep_seconds ? sleep_seconds : 0);

	// Verlinkung des Fertig-Links
	finish_url = (finish_url ? finish_url : false);

	var obj_obj = document.getElementById('restbauzeit-'+obj_id);

	// Titel festlegen, mit lokaler Fertigstellungszeit
	var title_string = 'Fertigstellung: ';
	var local_date = new Date((f_time+time_diff)*1000);
	title_string += mk2(local_date.getHours())+':'+mk2(local_date.getMinutes())+':'+mk2(local_date.getSeconds())+', '+local_date.getFullYear()+'-'+mk2(local_date.getMonth()+1)+'-'+mk2(local_date.getDate())+' (Lokalzeit); ';
	var remote_date = new Date(f_time*1000);
	title_string += mk2(remote_date.getHours())+':'+mk2(remote_date.getMinutes())+':'+mk2(remote_date.getSeconds())+', '+remote_date.getFullYear()+'-'+mk2(remote_date.getMonth()+1)+'-'+mk2(remote_date.getDate())+' (Serverzeit)';
	obj_obj.title = title_string;

	// Element leeren, damit keine leeren Textknoten stoeren
	while(obj_obj.firstChild)
		obj_obj.removeChild(obj_obj.firstChild);

	// Platzhalter fuer den spaeteren Countdown
	obj_obj.appendChild(document.createTextNode('.'));

	if(show_cancel)
	{ // Abbrechen-Link anlegen
		var cancel_link = document.createElement('a');
		cancel_link.href = '?cancel='+encodeURIComponent(obj_id)+'&'+url_suffix;
		cancel_link.className = 'abbrechen';
		cancel_link.appendChild(document.createTextNode('Abbrechen'));
		obj_obj.appendChild(cancel_link);
	}

	// In globales Array einfuegen, das jede Sekunde durchgearbeitet wird
	window.countdowns.push(new Array(obj_obj, f_time, sleep_seconds, finish_url, finish_callback));

	// Countdown gleich ausfuehren, damit der Platzhalter nicht sichtbar ist
	time_up();
}

function time_up()
{ // Wird jede Sekunde ausgefuehrt und zaehlt Countdowns und Uhren weiter
	// Uhren hochzaehlen
	local_time_up = new Date();
	server_time_up = new Date(local_time_up.getTime() - time_diff*1000);
	if(document.getElementById('time-local'))
		document.getElementById('time-local').firstChild.data = mk2(local_time_up.getHours())+':'+mk2(local_time_up.getMinutes())+':'+mk2(local_time_up.getSeconds());
	if(document.getElementById('time-server'))
		document.getElementById('time-server').firstChild.data = mk2(server_time_up.getHours())+':'+mk2(server_time_up.getMinutes())+':'+mk2(server_time_up.getSeconds());

	// Durch alle Countdowns durchlaufen
	for(var i=0; i<countdowns.length; i++)
	{
		if(!countdowns[i]) continue;

		var c = countdowns[i];
		var this_remain = Math.round((c[1]+time_diff)-local_time_up.getTime()/1000);

		if(this_remain < -c[2])
		{ // Countdown ist abgelaufen
			// Element leeren
			while(c[0].firstChild)
				c[0].removeChild(c[0].firstChild);

			// Fertig-Link anzeigen
			var link_fertig = document.createElement('a');
			if(c[3]) link_fertig.href = c[3];
			else link_fertig.href = '?'+url_suffix;
			link_fertig.className = 'fertig';
			link_fertig.title = 'Seite neu laden.';
			link_fertig.appendChild(document.createTextNode('Fertig.'));
			c[0].appendChild(link_fertig);

			// Callback-Funktion
			if(c[4]) c[4]();

			// Countdown entfernen
			countdowns[i] = false;

			continue;
		}

		// Verbleibende Zeit anzeigen
		c[0].firstChild.data = seconds2string(this_remain);
	}
}

/////////////////////////
/// Schnell-Shortcuts ///
/////////////////////////

key_elements = new Array();
// Die folgenden Elemente werden auf Shortcuts untersucht
check_key_elements = new Array('a', 'input', 'button', 'textarea', 'select');

function get_key_elements()
{ // Durchsucht das Dokument nach Shortcuts und legt Tastenverknuepfungen an
	var key_els;
	var accesskey;
	for(var i=0; i<check_key_elements.length; i++)
	{
		key_els = document.getElementsByTagName(check_key_elements[i]);
		for(var j=0; j<key_els.length; j++)
		{
			accesskey = key_els[j].getAttribute('accesskey');
			if(accesskey)
				key_elements[accesskey] = key_els[j];
		}
	}

	document.onkeypress = key_event;
}

function key_event(e)
{ // Wird beim Tastendruck aufgerufen, kuemmert sich um den Shortcut
	if(!e) e = window.event;

	if(e.target) node = e.target;
	else if (e.srcElement) node = e.srcElement;
	if(node.nodeName.toLowerCase() == "textarea" || (node.nodeName.toLowerCase() == "input" && node.getAttribute("type") != "checkbox" && node.getAttribute("type") != "radio") || node.nodeName.toLowerCase() == "select") return true;

	if(e.altKey || e.ctrlKey || e.metaKey)
		return true;

	var num;
	if(typeof e.charCode != "undefined") num = e.charCode;
	else if(typeof e.which != "undefined") num = e.which;
	else if(typeof e.keyCode != "undefined") num = e.keyCode;
	else return true;

	var chr = String.fromCharCode(num).toLowerCase();

	if(!key_elements[chr])
		return true;
	else
	{
		key_elements[chr].focus();

		var that_href;
		if(key_elements[chr].nodeName.toLowerCase() == "button")
			key_elements[chr].click();
		else if((key_elements[chr].nodeName.toLowerCase() == "a" || key_elements[chr].nodeName.toLowerCase() == "link") && (that_href = key_elements[chr].getAttribute("href")))
			location.href = that_href;
		else if(key_elements[chr].nodeName.toLowerCase() == "input" && (key_elements[chr].getAttribute("type") == "checkbox" || key_elements[chr].getAttribute("type") == "radio"))
			key_elements[chr].checked = !key_elements[chr].checked;

		if(key_elements[chr].onclick)
			key_elements[chr].onclick();
	}
}

///////////////////////////
/// JavaScript-Tooltips ///
///////////////////////////

function load_titles()
{ // Startet die Initialisierung der JavaScript-Tooltips
	// Tooltip-Element erstellen
	var js_title = document.createElement('div');
	js_title.setAttribute('id', 'js-title');
	js_title.style.position = 'absolute';
	js_title.appendChild(document.createTextNode('.'));
	js_title.className = 'hidden';
	document.getElementsByTagName('body')[0].appendChild(js_title);

	// Rekursive Initialisierung starten
	set_titles(document.getElementsByTagName('html')[0], 0);
}

var for_el;
var for_el_attr;

function set_titles(el, level)
{ // Wird rekursiv ausgefuehrt, initialisiert die JavaScript-Tooltips fuer einzelne Elemente
	if(el.getAttribute)
	{
		this_title = el.getAttribute('title');
		if(!this_title)
		{
			if(el.nodeName.toLowerCase() == 'label')
			{
				for_el_attr = el.getAttribute('for');
				if(for_el_attr)
				{
					for_el = document.getElementById(for_el_attr);
					if(for_el)
						this_title = for_el.getAttribute('title');
				}
			}
		}

		if(this_title)
		{
			el.onmouseover = show_title;
			el.onmouseout = hide_title;
			el.onmousemove = move_title;

			// titleAttribute ist das Dummy-Attribut fuer den Titel, damit der Browser seinen Tooltip nicht zusaetzlich anzeigt
			el.setAttribute('titleAttribute', this_title);
			el.removeAttribute('title');
		}
	}

	this_node[level] = el.firstChild;
	var next_element;
	while(this_node[level] != null)
	{
		if(this_node[level].nodeType == 1)
			set_titles(this_node[level], level+1);

		this_node[level] = this_node[level].nextSibling;
	}
}

var this_node = new Array();
var this_title = '';
var last_show_element;
var last_event_timeout;

function show_title(ev)
{ // Mouseover, Initialisieren eines JavaScript-Tooltips
	if(!ev) ev = window.event;

	var el = ev.target;
	if(!el)
		el = ev.srcElement;
	last_show_element = el;
	if(el)
	{
		var this_title = el.getAttribute('titleAttribute');
		if(this_title)
		{
			document.getElementById('js-title').firstChild.data = this_title;
			last_event_timeout = setTimeout('really_show_title()', 1000);

			var x_val = ev.pageX;
			if(!x_val)
				x_val = ev.clientX;
			var y_val = ev.pageY;
			if(!y_val)
				y_val = ev.clientY;
			document.getElementById('js-title').style.top = (y_val+10)+'px';
			document.getElementById('js-title').style.left = (x_val+10)+'px';
		}
	}
}

function set_title(a_el, a_title)
{
	if(a_el.onmouseover != show_title)
		a_el.title = a_title;
	else
	{
		a_el.setAttribute("titleAttribute", a_title);
		if(last_show_element && last_show_element == a_el)
			document.getElementById('js-title').firstChild.data = a_title;
	}
}

function really_show_title()
{ // Nach einer Sekunde Mouseover, Tooltip wird angezeigt
	if(last_show_element)
	{
		var this_title = last_show_element.getAttribute('titleAttribute');
		if(this_title)
		{
			/*var cur_el = last_show_element;
			var pos_left = cur_el.offsetLeft+last_show_element.offsetWidth;
			var pos_top = cur_el.offsetTop+last_show_element.offsetHeight;
			while(cur_el.offsetParent)
			{
				cur_el = cur_el.offsetParent;
				if(cur_el.nodeName.toLowerCase() == "body") break;
				pos_left += cur_el.offsetLeft;
				pos_top += cur_el.offsetTop;
			}
			document.getElementById('js-title').style.top = pos_top+"px";
			document.getElementById('js-title').style.left = pos_left+"px";*/
			document.getElementById('js-title').className = 'show';
		}
	}
}

function hide_title(ev)
{ // Mouseout, Tooltip wieder ausblenden
	document.getElementById('js-title').firstChild.data = '';
	document.getElementById('js-title').className = 'hidden';

	if(last_event_timeout)
		clearTimeout(last_event_timeout);
}

function move_title(ev)
{ // Bewegt den Tooltip mit der Maus
	if(!ev) ev = window.event;

	var x_val = ev.pageX;
	if(!x_val)
		x_val = ev.clientX;
	var y_val = ev.pageY;
	if(!y_val)
		y_val = ev.clientY;
	document.getElementById('js-title').style.top = (y_val+10)+'px';
	document.getElementById('js-title').style.left = (x_val+10)+'px';
}

////////////////////
/// Auto-Refresh ///
////////////////////

var refresh_callbacks = { };

Array.prototype.copy = function() { return this.slice(0, this.length); }

function refresh_ress(refresh_int, id, vorh, prod, lim)
{ // Initialisiert Auto-Refresh
	// Startzeit zur Neuberechnung

	if(slow_terminal) return false;

	var now_time = new Date();
	var start_time = now_time.getTime();

	res_now[id] = vorh.copy();
	if(!refresh_callbacks[id])
		refresh_callbacks[id] = [ ];

	// Interval zum Erhoehen der Rohstoffanzeigen
	setInterval(function(){increase_ress(start_time, id, vorh, prod, lim)}, refresh_int);
}

function increase_ress(start_time, id, vorh, prod, lim)
{ // Aktualisiert die Rohstoffanzeigen
	// Zeitdifferenz berechnen

	var now_time = new Date();
	var time_passed = (now_time.getTime()-start_time)/3600000;

	for(var i=0; i<=4; i++)
	{
		if(res_now[id][i] >= lim[i]) continue;
		res_now[id][i] = vorh[i]+prod[i]*time_passed;
		if(res_now[id][i] > lim[i])
			res_now[id][i] = lim[i];
	}

	// Werte berechnen und in die Anzeige schreiben
	document.getElementById(id+'-carbon').firstChild.data = ths(res_now[id][0]);
	document.getElementById(id+'-aluminium').firstChild.data = ths(res_now[id][1]);
	document.getElementById(id+'-wolfram').firstChild.data = ths(res_now[id][2]);
	document.getElementById(id+'-radium').firstChild.data = ths(res_now[id][3]);
	document.getElementById(id+'-tritium').firstChild.data = ths(res_now[id][4]);

	for(var i=0; i<refresh_callbacks[id].length; i++)
		refresh_callbacks[id][i]();
}

////////////////////////////
/// Grafische Funktionen ///
////////////////////////////

fadeout_elements = new Array();

function setOpacity(el_key,opacity)
{ // Setzt die Transparenz eine Elements
	el = fadeout_elements[el_key];
	opacity = (opacity == 100)?99:opacity;
	// IE
	el.style.filter = "alpha(opacity:"+opacity+")";
	// Safari < 1.2, Konqueror
	el.style.KHTMLOpacity = opacity/100;
	// Old Mozilla
	el.style.MozOpacity = opacity/100;
	// Safari >= 1.2, Firefox and Mozilla, CSS3
	el.style.opacity = opacity/100
}

function popup_message(message, classn, calling_node)
{ // Oeffnet kurzzeitig einen kleinen Kasten mit einer Statusmeldung
	var timeout = 500;

	popup_el = document.createElement('p');
	if(classn) popup_el.className = 'popup '+classn;
	else popup_el.className = 'popup';

	popup_el.appendChild(document.createTextNode(message));

	posx = calling_node.offsetLeft+10;
	posy = calling_node.offsetTop+calling_node.offsetHeight+5;
	parent_offset = calling_node;
	while(parent_offset.offsetParent)
	{
		parent_offset = parent_offset.offsetParent;
		if(parent_offset.nodeName.toLowerCase() == 'body') break;
		posx += parent_offset.offsetLeft;
		posy += parent_offset.offsetTop;
	}

	popup_el.style.position = 'absolute';
	popup_el.style.top = posy+'px';
	popup_el.style.left = (posx+calling_node.offsetWidth)+'px';

	body_el = document.getElementsByTagName('body')[0];
	body_el.appendChild(popup_el);

	var right_point = popup_el.offsetLeft+popup_el.offsetWidth;
	var parent_width = body_el.offsetWidth;

	if(right_point > parent_width)
		popup_el.style.left = (parent_width-popup_el.offsetWidth)+'px';

	var array_key = fadeout_elements.length;
	fadeout_elements[array_key] = popup_el;
	setTimeout('popup_fadeout('+array_key+');', timeout);
}

function popup_fadeout(el_key)
{ // Laesst eine obige Statusmeldung langsam ausblenden
	el = fadeout_elements[el_key];

	if(!slow_terminal)
	{
		steps = 15;
		timel = 4000;

		setOpacity(el_key,100); // To prevent flicker in Firefox
		                        // The first time the opacity is set
		                        // the element flickers in Firefox
		fadeStep = 100/steps;
		timeStep = timel/steps;
		opacity = 100;
		timel = 100;

		while (opacity >=0) {
			window.setTimeout("setOpacity("+el_key+","+opacity+")",timel);
			opacity -= fadeStep;
			timel += timeStep;
		}
	}
	window.setTimeout('fadeout_elements['+el_key+'].parentNode.removeChild(fadeout_elements['+el_key+']);', timel);
}

//////////////////
/// Ladestatus ///
//////////////////

var loading_instances = 0;
var loading_element = false;

function add_loading_instance()
{ // Gibt an, dass ein Ladevorgang gestartet wurde
	if(!loading_element)
	{
		loading_element = document.createElement('p');
		loading_element.id = 'loading';
		loading_element.appendChild(document.createTextNode('Laden...'));
		document.getElementsByTagName('body')[0].appendChild(loading_element);
	}
	loading_instances++;
}

function remove_loading_instance()
{ // Gibt an, dass ein Ladevorgang abgeschlossen wurde
	loading_instances--;
	if(loading_instances <= 0)
	{
		loading_element.parentNode.removeChild(loading_element);
		loading_element = false;
	}
}

/////////////////////////////////////////////////////
/// Benutzernamen-Autocomplete in Formularfeldern ///
/////////////////////////////////////////////////////

users_list_timeout = false;
users_list = false;
users_list_selected = false;
users_list_cache = new Object();

function activate_users_list(element)
{ // Aktiviert Autocomplete fuer ein Eingabefeld
	// Eventhandler beim Tippen
	element.onkeypress = make_users_list;

	// Autocomplete des Browsers abschalten
	element.setAttribute('autocomplete', 'off');

	// Opera fix
	element.style.position = 'relative';
}

function make_users_list(e)
{ // Wird beim Tippen in einem Autocomplete-Feld aufgerufen
// Sorgt dafuer, dass 0,5 Sekunden abgewartet wird, ob weitergetippt
// wird, bevor do_make_users_list() die Auswahlliste erzeugt

	if(!e) e = window.event;
	if(e.target) node=e.target;
	else if(e.srcElement) node=e.srcElement;
	else return;

	// Eventuelle andere Wartevorgaenge (0,5 Sekunden) abbrechen
	if(users_list_timeout) clearTimeout(users_list_timeout);

	// Wartevorgang einleiten
	users_list_timeout = setTimeout('do_make_users_list(node)', 1);

	// Eventhandler initialisieren, die die Benutzerliste einblenden oder verschwinden lassen
	node.onblur = function(){clearTimeout(users_list_timeout);}
	node.onfocus = function(){make_users_list(e);}
}

function do_make_users_list(node)
{ // Erzeugt die Autocomplete-Auswahlliste unter dem Eingabefeld, wird von make_users_list() kontrolliert
	if(node.value.length < last_min_chars)
	{ // Standardmaessig wird eine Auswahl erst ab zwei eingetippten Zeichen ermoeglicht
		if(users_list)
		{ // Eventuelle bereits vorhandene Auswahlliste entfernen
			users_list.parentNode.removeChild(users_list);
			users_list = false;
			users_list_selected = false;
		}
		return;
	}

	// Beim Verlassen des Feldes wird die Liste ausgeblendet
	node.onblur = function(){t=this; setTimeout('if(users_list){users_list.parentNode.removeChild(users_list);users_list=false;users_list_selected=false;}',1);}

	// Wenn die Liste angezeigt wird, muss das Hoch- und Runternavigieren durch Pfeiltasten ermoeglicht werden
	node.onkeypress = function(e)
	{
		if(!e) e = window.event;

		if(!_SARISSA_IS_IE && !e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey)
		{ // IE beherrscht die Hoch-Runter-Tasten nur bei onkeyup, siehe weiter unten
			if((e.DOM_VK_DOWN && e.keyCode == e.DOM_VK_DOWN) || e.keyCode == 40)
			{ // Runter-Taste: In der Auswahl nach unten huepfen
				users_list_select(node, 1);
				return false;
			}
			else if((e.DOM_VK_UP && e.keyCode == e.DOM_VK_UP) || e.keyCode == 38)
			{ // Hoch-Taste: In der Auswahl nach oben huepfen
				users_list_select(node, -1);
				return false;
			}
		}

		if((e.DOM_VK_RETURN && e.keyCode == e.DOM_VK_RETURN) || (e.DOM_VK_ENTER && e.keyCode == e.DOM_VK_ENTER) || e.keyCode == 13 || e.keyCode == 14)
		{ // Bei Enter soll nicht das Formular abgesendet werden, sondern die Liste verschwinden
			if(users_list)
			{
				users_list.parentNode.removeChild(users_list);
				users_list = false;
				users_list_selected = false;
				return false;
			}
		}
		if((e.DOM_VK_TAB && e.keyCode == e.DOM_VK_TAB) || e.keyCode == 9)
		{ // Tab: Naechstes Eingabefeld, Liste verschwindibus
			if(users_list)
			{
				users_list.parentNode.removeChild(users_list);
				users_list = false;
				users_list_selected = false;
				return true;
			}
		}

		// Keine dieser Tasten? Normaler Eventhandler wie sonst auch,
		// verzoegerte Aktualisierung der Auswahlliste einleiten
		make_users_list(e);
	}

	if(_SARISSA_IS_IE)
	{ // Hoch-Runter-Tasten fuer den IE, siehe oben
		node.onkeyup = function(e)
		{
			if(!e) e = window.event;
			if(!e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey)
			{
				if((e.DOM_VK_DOWN && e.keyCode == e.DOM_VK_DOWN) || e.keyCode == 40)
				{ // Runter-Taste
					users_list_select(node, 1);
					return false;
				}
				else if((e.DOM_VK_UP && e.keyCode == e.DOM_VK_UP) || e.keyCode == 38)
				{ // Hoch-Taste
					users_list_select(node, -1);
					return false;
				}
			}
		}
	}

	// Eventhandlerdefinitionen fertig
	// Nun die Liste per AJAX aktualisieren

	// Wissenswert, ob schon eine Liste da ist, ob sie spaeter entfernt werden muss
	old_l = false;
	if(users_list) old_l = users_list;

	// Listenelement heimlich erzeugen, aber erst nach vollstaendigem
	// Laden in den DOM-Baum klatschen, damit die Liste nicht kurzzeitig
	// verschwindet.
	l = document.createElement('ul');
	l.className = 'autocomplete';
	l.style.position = 'absolute';
	// Position unterhalb des Eingabefeldes
	l.style.top = (node.offsetTop+node.offsetHeight)+'px';
	l.style.left = node.offsetLeft+'px';
	l.style.width = node.offsetWidth+'px';

	// Schauen, ob die Liste bereits geladen ist
	var cache_item = '';
	for(var i in users_list_cache)
	{
		if(node.value.substr(0, i.length) == i && i.length > cache_item.length)
			cache_item = i;
	}

	if(cache_item)
	{ // Liste ist bereits geladen, Cache benutzen
		var c = users_list_cache[cache_item];
		var new_list = new Array();
		for(var i=0; i<c.length; i++)
		{
			if(c[i].length > node.value.length && c[i].substr(0, node.value.length) == node.value)
				new_list.push(c[i]);
		}
		do_create_users_list(new_list);
	}
	else
	{ // Mithilfe der Sarissa-Bibliothek AJAX-Request durchfuehren
		var xmlhttp = new XMLHttpRequest();
		var request_url = h_root+'/login/res/ajax.php?action=userlist&query='+encodeURIComponent(node.value)+'&'+url_suffix+'&database='+encodeURIComponent(database_id);
		xmlhttp.open('GET', request_url, true);
		xmlhttp.onreadystatechange = function() {
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200)
			{ // Request war erfolgreich
				// <result> sind die Elemente, die die Benutzernamen enthalten
				users_list_cache[node.value] = new Array();
				var results = xmlhttp.responseXML.getElementsByTagName('result');
				for(var i=0; i<results.length; i++)
				{
					v = results[i].firstChild.data;
					users_list_cache[node.value].push(v);
				}

				do_create_users_list(users_list_cache[node.value]);
			}
		}
		xmlhttp.send(null);
	}
}

function do_create_users_list(list)
{
	for(var i=0; i<list.length; i++)
	{
		// Benutzernamen in die noch nicht angezeigte Liste einfuegen
		var next_li = document.createElement('li');

		// Eventhandler einbauen, die das Auswaehlen der Benutzernamen und das Uebernehmen in die Eingabefelder ermoeglichen
		next_li.onclick = function(){node.value = this.firstChild.data;if(users_list){users_list.parentNode.removeChild(users_list);users_list=false;users_list_selected=false;}}
		next_li.onmouseover = function(){window.userlist_active_before_mouse=(this.className=='selected');this.className = 'selected';}
		next_li.onmouseout = function(){if(!userlist_active_before_mouse)this.className = '';}
		next_li.appendChild(document.createTextNode(list[i]));
		l.appendChild(next_li);
	}

	if(old_l && old_l.parentNode)
	{ // Wenn bereits eine Auswahlliste da ist, diese entfernen
		old_l.parentNode.removeChild(users_list);
		users_list = false;
		users_list_selected = false;
	}
	var do_insert = true;
	if(list.length <= 0) do_insert = false;
	else if(list.length == 1 && node.value.toLowerCase() == v.toLowerCase()) do_insert = false;

	if(do_insert) // Es gibt Uebereinstimmungen, die Liste enthaelt Eintraege
	{
		// Liste in den DOM-Baum klatschen
		node.parentNode.insertBefore(l, node.nextSibling);
		users_list = l;
	}
	else users_list = false;
}

function users_list_select(node, move_cursor) // move_cursor gibt die Anzahl der Eintraege an, um die der Cursor nach unten verschoben werden soll
{ // Ermoeglicht das Auswaehlen in der Auswahlliste mithilfe der Hoch-Runter-Tasten
	l = node.nextSibling;
	if(!l || l.className != 'autocomplete') return; // Es existiert keine Auswahlliste

	if(typeof users_list_selected == 'boolean' && !users_list_selected) users_list_selected = -1; // Es ist noch kein Eintrag ausgewaehlt
	users_list_selected_old = users_list_selected;
	users_list_selected += move_cursor;

	if(users_list_selected < 0) users_list_selected = l.childNodes.length-1; // Vom Anfang bei Nach oben ans Ende springen
	else if(users_list_selected >= l.childNodes.length) users_list_selected = 0; // Vom Ende bei Nach unten an den Anfang springen

	// Status des vormals ausgewaehlten Punktes auf nicht ausgewaehlt setzen
	if(users_list_selected_old >= 0 && l.childNodes[users_list_selected_old] && !userlist_active_before_keyboard)
		l.childNodes[users_list_selected_old].className = '';

	if(l.childNodes[users_list_selected])
	{
		// Neuen Eintrag als aktiv markieren
		window.userlist_active_before_keyboard = (l.childNodes[users_list_selected].className=='selected');
		l.childNodes[users_list_selected].className = 'selected';

		// Benutzernamen ins Eingabefeld uebernehmen
		node.value = l.childNodes[users_list_selected].firstChild.data;
	}
	else
	{
		// Wenn es den neuen Eintrag auf mysterioese Weise nicht geben sollte, wird die Liste zurueckgesetzt
		window.userlist_active_before_keyboard = false;
		users_list_selected = false;
	}
}

//////////////////////////////////////////
/// Beschleunigung von Karten-Aktionen ///
//////////////////////////////////////////

preloaded_systems = new Array();
preloading_systems = new Array();
function preload_systems(systems)
{ // Laed die uebergebenen Systeme per AJAX in preloaded_systems und verwaltet
// preloading_systems, sodass von aussen sicher und problemlos auf Systeme zugegriffen
// werden kann.

	if(typeof systems != 'object')
	{ // Es wurde nur ein System uebergeben, Array daraus erzeugen
		pr_system = systems;
		systems = new Array();
		systems.push(pr_system);
	}

	request_url = h_root+'/login/res/ajax.php?action=universe&'+url_suffix+'&database='+encodeURIComponent(database_id);
	var c = 0;
	for(var i=0; i<systems.length; i++)
	{ // Systeme als GET-Request an die URL haengen
		if(typeof preloaded_systems[systems[i]] != 'undefined' || preloading_systems[systems[i]])
			continue; // System wird/ist bereits geladen
		request_url += '&system[]='+encodeURIComponent(systems[i]);
		preloading_systems[systems[i]] = true;
		c++;
	}

	if(c <= 0) return; // Es gibt nichts zu laden

	add_loading_instance(); // Blendet einen Informationskasten ueber den Ladestatus ein, siehe oben

	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open('GET', request_url, true);

	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4)
		{
			if(xmlhttp.status == 200 && xmlhttp.responseXML)
			{
				system_results = xmlhttp.responseXML.getElementsByTagName('system');
				for(i=0; i<system_results.length; i++)
				{ // Durch alle Systeme wandern
					system_number = system_results[i].getAttribute('number');
					preloaded_systems[system_number] = new Array();
					system_info = system_results[i].childNodes;
					for(j=0; j<system_info.length; j++)
					{ // Alle Planeten auswerten
						if(system_info[j].nodeType != 1) continue;
						planet_number = system_info[j].getAttribute('number');
						preloaded_systems[system_number][planet_number] = new Array();
						preloading_systems[system_number] = false;
						planet_infos = system_info[j].childNodes;
						if(planet_infos.length <= 0) continue;
						for(k=0; k<planet_infos.length; k++)
						{
							if(planet_infos[k].nodeType != 1) continue;
							var this_info = '';
							if(planet_infos[k].nodeName.toLowerCase() == 'truemmerfeld')
								this_info = new Array(planet_infos[k].getAttribute('carbon'), planet_infos[k].getAttribute('aluminium'), planet_infos[k].getAttribute('wolfram'), planet_infos[k].getAttribute('radium'));
							else if(planet_infos[k].childNodes.length > 0)
								this_info = planet_infos[k].firstChild.data;
							preloaded_systems[system_number][planet_number][planet_infos[k].nodeName.toLowerCase()] = this_info;
						}
					}
				}
				for(var i in systems)
				{ // Fehlgeschlagene Systeme markieren
					if(typeof preloaded_systems[systems[i]] == 'undefined')
						preloaded_systems[systems[i]] = false;
				}
			}

			// Ladevorgang abgeschlossen, Informationskasten ausblenden
			remove_loading_instance();
		}
	}

	xmlhttp.send(null);
}

function change_digit(a_number, a_digit, a_change)
{
	var d = Math.floor((a_number%Math.pow(10, a_digit+1))/Math.pow(10, a_digit));
	var d_new = d+a_change;
	while(d_new >= 10) d_new -= 10;
	while(d_new < 0) d_new += 10;
	a_number += (d_new-d)*Math.pow(10, a_digit);
	return a_number;
}

function fast_action(node, action_type, galaxy, system, planet)
{ // Fuehrt einen Flottenversandt oder Aehnliches durch kurzen Klick aus der Karte aus
	// AJAX-Request ausfuehren (synchron)
	var xmlhttp = new XMLHttpRequest();
	var request_url = h_root+'/login/res/ajax.php?action='+encodeURIComponent(action_type)+'&action_galaxy='+encodeURIComponent(galaxy)+'&action_system='+encodeURIComponent(system)+'&action_planet='+encodeURIComponent(planet)+'&'+url_suffix+'&database='+encodeURIComponent(database_id);
	xmlhttp.open('GET', request_url, false);
	xmlhttp.send(null);

	// Bei Erfolg kurze Statusmeldung (popup_message() siehe oben) anzeigen, wird automatisch ausgeblendet
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200 && xmlhttp.responseXML)
		popup_message(xmlhttp.responseXML.getElementsByTagName('result')[0].firstChild.data, xmlhttp.responseXML.getElementsByTagName('classname')[0].firstChild.data, node);

	return false;
}
