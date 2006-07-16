///////////////////////////////////////////////////////////////////////////
/// S-U-A JavaScripts, Keine Bildschirmaenderungen, Performance-Stufe 0 ///
///////////////////////////////////////////////////////////////////////////

/////////////////////////////
/// Allgemeine Funktionen ///
/////////////////////////////

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

function mk2(string)
{ // Fuegt eine 0 an eine Zahl vorne an, sodass diese zweistellig wird
	string = ''+string;
	while(string.length < 2)
		string = '0'+string;

	return string;
}

function myParseInt(string)
{
	var count = parseInt(string);
	if(isNaN(count) || count < 0)
		return 0;
	else
		return count;
}


/////////////////////////////
/// Dummy-Funktionen ///
/////////////////////////////

var time_diff = 0;

function init_countdown(){ return false; }

function activate_users_list() { return false; }