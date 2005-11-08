<?php
	require('../engine/include.php');
	header('Content-type: text/javascript; charset=ISO-8859-1');
	header('Cache-control: max-age=152800');
	header('Expires: '.strftime('%a, %d %b %Y %T %Z', time()+152800));
?>
function set_time_globals(server_time)
{
	window.local_time_obj = new Date();
	window.local_time = Math.round(local_time_obj.getTime() / 1000);
	window.time_diff = local_time-server_time;

	window.countdowns = new Array();
}

function mk2(string)
{
	string = ''+string;
	while(string.length < 2)
		string = '0'+string;

	return string;
}

function time_up()
{
	local_time_up = new Date();
	server_time_up = new Date(local_time_up.getTime() - time_diff*1000);
	document.getElementById('time-local').innerHTML = mk2(local_time_up.getHours())+':'+mk2(local_time_up.getMinutes())+':'+mk2(local_time_up.getSeconds());
	document.getElementById('time-server').innerHTML = mk2(server_time_up.getHours())+':'+mk2(server_time_up.getMinutes())+':'+mk2(server_time_up.getSeconds());

	for(var codo_key in countdowns)
	{
		var codo = countdowns[codo_key];
		if(!codo[0] || !codo[1])
			continue;
		var this_remain = Math.round((codo[1]+time_diff)-local_time_up.getTime()/1000);

		if(this_remain < 0)
		{
			document.getElementById('restbauzeit-'+codo[0]).innerHTML = '<a href="?" class="fertig" title="Seite neu laden.">Fertig.</a>';
			delete countdowns[codo_key];
			continue;
		}

		var this_timestring = '';
		if(this_remain >= 86400)
		{
			this_timestring += Math.floor(this_remain/86400)+'&thinsp;<abbr title="Tag';
			if(this_remain >= 172800)
				this_timestring += 'e';
			this_timestring += '">d</abbr> ';
			this_remain = this_remain % 86400;
		}

		this_timestring += mk2(Math.floor(this_remain/3600))+':'+mk2(Math.floor((this_remain%3600)/60))+':'+mk2(Math.floor(this_remain%60));
		if(codo[2])
			this_timestring += ' <a href="?cancel='+encodeURIComponent(codo[0])+'" class="abbrechen">Abbrechen</a>';

		document.getElementById('restbauzeit-'+codo[0]).innerHTML = this_timestring;
	}
}

function init_countdown(obj_id, f_time)
{
	var show_cancel = true;
	if(init_countdown.arguments.length >= 3 && !init_countdown.arguments[2])
		show_cancel = false;

	var title_string = 'Fertigstellung: ';
	var local_date = new Date((f_time+time_diff)*1000);
	title_string += mk2(local_date.getHours())+':'+mk2(local_date.getMinutes())+':'+mk2(local_date.getSeconds())+', '+local_date.getFullYear()+'-'+mk2(local_date.getMonth()+1)+'-'+mk2(local_date.getDate())+' (Lokalzeit); ';

	var remote_date = new Date(f_time*1000);
	title_string += mk2(remote_date.getHours())+':'+mk2(remote_date.getMinutes())+':'+mk2(remote_date.getSeconds())+', '+remote_date.getFullYear()+'-'+mk2(remote_date.getMonth()+1)+'-'+mk2(remote_date.getDate())+' (Serverzeit)';

	document.getElementById('restbauzeit-'+obj_id).setAttribute('title', title_string);
	window.countdowns.push(new Array(obj_id, f_time, show_cancel));

	time_up();
}

function ths(old_count)
{
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
	new_count = new_count.join("<?=utf8_jsentities(THS_UTF8)?>");
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

key_elements = new Array();
check_key_elements = new Array('a', 'input', 'button', 'textarea', 'select');

function get_key_elements()
{
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
{
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

function load_titles()
{
	var js_title = document.createElement('div');
	js_title.setAttribute('id', 'js-title');
	js_title.style.position = 'absolute';
	js_title.appendChild(document.createTextNode(''));
	js_title.className = 'hidden';
	document.getElementsByTagName('body')[0].appendChild(js_title);

	set_titles(document.getElementsByTagName('html')[0], 0);
}

var this_node = new Array();
var this_title = '';
var last_show_element;
var last_event_timeout;

function show_title(ev)
{
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
		}
	}
}

function really_show_title()
{
	if(last_show_element)
	{
		var this_title = last_show_element.getAttribute('titleAttribute');
		if(this_title)
			document.getElementById('js-title').className = 'show';
	}
}

function move_title(ev)
{
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

function hide_title(ev)
{
	document.getElementById('js-title').firstChild.data = '';
	document.getElementById('js-title').className = 'hidden';

	if(last_event_timeout)
		clearTimeout(last_event_timeout);
}

var for_el;
var for_el_attr;

function set_titles(el, level)
{
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
			el.onmousemove = move_title;
			el.onmouseout = hide_title;

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

function refresh_ress(refresh_int, carbon_vorh, aluminium_vorh, wolfram_vorh, radium_vorh, tritium_vorh, carbon_prod, aluminium_prod, wolfram_prod, radium_prod, tritium_prod)
{
	window.carbon_vorh = carbon_vorh;
	window.aluminium_vorh = aluminium_vorh;
	window.wolfram_vorh = wolfram_vorh;
	window.radium_vorh = radium_vorh;
	window.tritium_vorh = tritium_vorh;

	window.carbon_prod = carbon_prod;
	window.aluminium_prod = aluminium_prod;
	window.wolfram_prod = wolfram_prod;
	window.radium_prod = radium_prod;
	window.tritium_prod = tritium_prod;

	var now_time = new Date();
	window.last_increase_ress = now_time.getTime();

	setInterval("increase_ress()", refresh_int);
}

function increase_ress()
{
	var now_time = new Date();
	var time_diff = (now_time.getTime()-window.last_increase_ress)*0.000000277777777777778;
	window.carbon_vorh += window.carbon_prod*time_diff;
	window.aluminium_vorh += window.aluminium_prod*time_diff;
	window.wolfram_vorh += window.wolfram_prod*time_diff;
	window.radium_vorh += window.radium_prod*time_diff;
	window.tritium_vorh += window.tritium_prod*time_diff;

	document.getElementById('ress-carbon').firstChild.data = ths(window.carbon_vorh);
	document.getElementById('ress-aluminium').firstChild.data = ths(window.aluminium_vorh);
	document.getElementById('ress-wolfram').firstChild.data = ths(window.wolfram_vorh);
	document.getElementById('ress-radium').firstChild.data = ths(window.radium_vorh);
	document.getElementById('ress-tritium').firstChild.data = ths(window.tritium_vorh);

	window.last_increase_ress = now_time.getTime();
}