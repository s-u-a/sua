////////////////////////////////////////////////////////////////////////
/// S-U-A JavaScripts, CPU-genuegsamste Version, Performance-Stufe 1 ///
////////////////////////////////////////////////////////////////////////


/////////////////////////////
/// Allgemeine Funktionen ///
/////////////////////////////

function set_time_globals(server_time)
{ // Initialisiert Anfangsveriablen
	// Differenz zwischen Lokal- und Globalzeit feststellen
	window.local_time_obj = new Date();
	window.local_time = Math.round(local_time_obj.getTime() / 1000);
	window.time_diff = local_time-server_time;

	// Initialisieren der Countdowns
	window.countdowns = new Array();
	// Interval wird definiert in login/scripts/include.php, nach HTML-Angabe der Serverzeit
}

function mk2(string)
{ // Fuegt eine 0 an eine Zahl vorne an, sodass diese zweistellig wird
	string = ''+string;
	while(string.length < 2)
		string = '0'+string;

	return string;
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

function ths(old_count)
{ // Fuegt Tausendertrennzeichen ein
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


////////////////////////
/// Countdown-System ///
////////////////////////

function init_countdown(obj_id, f_time)
{ // Initialisiert einen Countdown
	var show_cancel = true; // Abbruchbutton anzeigen
	if(init_countdown.arguments.length >= 3 && !init_countdown.arguments[2])
		show_cancel = false;

	var sleep_seconds = init_countdown.arguments[3]; // Zeit bleibt einige Sekunden auf 0 stehen, bevor zu Fertig gewechselt wird

	var finish_url = init_countdown.arguments[4]; // Verlinkunk des Fertig-Links

	var obj_obj = document.getElementById('restbauzeit-'+obj_id);

	// Titel des Elements festlegen (Lokalzeit der Fertigstellung eintragen)
	var title_string = 'Fertigstellung: ';
	var local_date = new Date((f_time+time_diff)*1000);
	title_string += mk2(local_date.getHours())+':'+mk2(local_date.getMinutes())+':'+mk2(local_date.getSeconds())+', '+local_date.getFullYear()+'-'+mk2(local_date.getMonth()+1)+'-'+mk2(local_date.getDate())+' (Lokalzeit); ';
	var remote_date = new Date(f_time*1000);
	title_string += mk2(remote_date.getHours())+':'+mk2(remote_date.getMinutes())+':'+mk2(remote_date.getSeconds())+', '+remote_date.getFullYear()+'-'+mk2(remote_date.getMonth()+1)+'-'+mk2(remote_date.getDate())+' (Serverzeit)';
	obj_obj.title = title_string;

	// Element leeren, damit keine stoerenden leeren Textknoten vorhanden sind
	while(obj_obj.firstChild)
		obj_obj.removeChild(obj_obj.firstChild);

	// Restbauzeit berechnen
	var local_time = new Date();
	var remain = Math.round((f_time+time_diff)-local_time.getTime()/1000);

	// Countdown anzeigen
	obj_obj.appendChild(document.createTextNode(seconds2string(remain)));

	if(show_cancel)
	{
		var cancel_link = document.createElement('a');
		cancel_link.setAttribute('href', '?cancel='+encodeURIComponent(obj_id)+'&'+window.session_cookie+'='+encodeURIComponent(window.session_id));
		cancel_link.className = 'abbrechen';
		cancel_link.appendChild(document.createTextNode('Abbrechen'));
		obj_obj.appendChild(cancel_link);
	}

	// Zum Countdowns-Array hinzufuegen, wird jede Sekunde abgearbeitet
	window.countdowns.push(new Array(obj_obj, remain, sleep_seconds, finish_url));
}

function time_up()
{ // Aktualisiert die Countdowns
	local_time_up = new Date();
	server_time_up = new Date(local_time_up.getTime() - time_diff*1000);
	document.getElementById('time-local').firstChild.data = mk2(local_time_up.getHours())+':'+mk2(local_time_up.getMinutes())+':'+mk2(local_time_up.getSeconds());
	document.getElementById('time-server').firstChild.data = mk2(server_time_up.getHours())+':'+mk2(server_time_up.getMinutes())+':'+mk2(server_time_up.getSeconds());

	for(var i=0; i<countdowns.length; i++)
	{
		if(!countdowns[i]) continue;

		// Count down
		countdowns[i][1]--;

		var c = countdowns[i];

		if(c[1] < -c[2])
		{ // Countdown ist abgelaufen
			// Element leeren
			while(c[0].firstChild)
				c[0].removeChild(c[0].firstChild);
			// Fertig-Link anzeigen
			var link_fertig = document.createElement('a');
			if(c[3]) link_fertig.href = c[3];
			else link_fertig.href = '?'+session_cookie+'='+encodeURIComponent(session_id);
			link_fertig.className = 'fertig';
			link_fertig.title = 'Seite neu laden.';
			link_fertig.appendChild(document.createTextNode('Fertig.'));
			c[0].appendChild(link_fertig);

			// Countdown abbrechen
			countdowns[i] = false;
			continue;
		}

		c[0].firstChild.data = seconds2string(c[1]);
	}
}


////////////////////////
/// Schnel-Shortcurs ///
////////////////////////

key_elements = new Array();
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

	document.onkeyup = key_event;
}

function key_event(e)
{ // Wird beim Tastendruck aufgerufen, kuemmert sich um den Shortcut
	if(!e) e = window.event;

	if(e.target) node = e.target;
	else if (e.srcElement) node = e.srcElement;
	if(node.nodeName.toLowerCase() == "textarea" || (node.nodeName.toLowerCase() == "input" && node.getAttribute("type") != "checkbox" && node.getAttribute("type") != "radio") || node.nodeName.toLowerCase() == "select") return true;

	if(e.altKey || e.ctrlKey)
		return true;

	var num;
	if(e.which) num = e.which;
	else if(e.keyCode) num = e.keyCode;
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
{ // Dummy function, not enabled in performance mode 2
	return true;
}


////////////////////
/// Auto-Refresh ///
////////////////////

function refresh_ress(refresh_int, carbon_vorh, aluminium_vorh, wolfram_vorh, radium_vorh, tritium_vorh, carbon_prod, aluminium_prod, wolfram_prod, radium_prod, tritium_prod)
{ // Initialisiert Auto-Refresh
	// Vorhandene Rohstoffe speichern
	window.carbon_vorh = carbon_vorh;
	window.aluminium_vorh = aluminium_vorh;
	window.wolfram_vorh = wolfram_vorh;
	window.radium_vorh = radium_vorh;
	window.tritium_vorh = tritium_vorh;

	// Produktion speichern
	window.carbon_prod = carbon_prod;
	window.aluminium_prod = aluminium_prod;
	window.wolfram_prod = wolfram_prod;
	window.radium_prod = radium_prod;
	window.tritium_prod = tritium_prod;

	// Automatische Hochzaehlung initialisieren
	setInterval("increase_ress()", refresh_int);
	window.refresh_int = refresh_int/3600000;
}

function increase_ress()
{ // Zaehlt die Rohstoffe hoch
	// Variablen der vorhandenen Rohstoffe hochzaehlen
	window.carbon_vorh += window.carbon_prod*refresh_int;
	window.aluminium_vorh += window.aluminium_prod*refresh_int;
	window.wolfram_vorh += window.wolfram_prod*refresh_int;
	window.radium_vorh += window.radium_prod*refresh_int;
	window.tritium_vorh += window.tritium_prod*refresh_int;

	// Neue Rohstoffvorraete anzeigen
	document.getElementById('ress-carbon').firstChild.data = ths(window.carbon_vorh);
	document.getElementById('ress-aluminium').firstChild.data = ths(window.aluminium_vorh);
	document.getElementById('ress-wolfram').firstChild.data = ths(window.wolfram_vorh);
	document.getElementById('ress-radium').firstChild.data = ths(window.radium_vorh);
	document.getElementById('ress-tritium').firstChild.data = ths(window.tritium_vorh);
}


////////////
/// AJAX ///
////////////

function activate_users_list(element)
{ /* Not activated in performance mode 1 */
	return false;
}