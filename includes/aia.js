function update_token()
{
	if (this.readyState === 4) {
		if (this.status >= 200 && this.status < 400){
			document.getElementById( AIA.field ).value = this.responseText;
		}
	}
}

var data	= 'action=aia_field_update';
var request = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
request.open('POST', AIA.ajaxurl, true);
request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
request.onreadystatechange = update_token;
request.send(data);