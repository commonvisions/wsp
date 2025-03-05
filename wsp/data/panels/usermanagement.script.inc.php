<script>

function checkForUnixNames(givenValue, checkedField, fieldName) {
	var tempValue = '';
	var errorCount = 0;
	for (g=0; g<givenValue.length; g++) {
		if (givenValue[g] < "0" || givenValue[g] > "9") {
			if (givenValue[g] < "a" || givenValue[g] > "z") {
				if (givenValue[g] < "A" || givenValue[g] > "Z") {
					if (givenValue[g] != "." && givenValue[g] != "_") {
						errorCount++;
					}
					else {
						tempValue += givenValue[g];
					}
				}
				else {
					tempValue += givenValue[g];
				}
			}
			else {
				tempValue += givenValue[g];
			}
		}
		else {
			tempValue += givenValue[g];
		}
	}
	if (errorCount > 0) {
		alert ("Bitte verwenden Sie im Feld '" + fieldName + "' nur Buchstaben ('a-z'), Zahlen ('0-9'), Punkt ('.') und/oder Unterstrich '_'");
		document.getElementById(checkedField).value = tempValue;
		return false;
	}
	else {
		return true;
	}
}

function checklengthuser(){
	if(document.getElementById('new_username').value.length>2){
		if (checkForUnixNames($('#new_username').val(), 'new_username', 'Username')) {
			if(document.getElementById('new_realname').value.length>1){
				if(document.getElementById('new_email').value.length>8){
					$('#frmcreateuser').submit();
					return false;
				}
				else{
					alert("<?php echo returnIntLang('usermanagement new user setup email', false); ?>");
					return false;
				}
			}
			else if ($('#new_position').val()=='webuser') {
				$('#frmcreateuser').submit();
				return false;
			} 
			else {
				alert("<?php echo returnIntLang('usermanagement new user setup real name', false); ?>");
				return false;
			}
			document.getElementById('frmcreateuser').submit(); return false;
			return false;
		}
	}
	else {
		alert("<?php echo returnIntLang('usermanagement new username too short', false); ?>");
		return false;
	}
}

function checklengthpass(){
	if (document.getElementById('my_new_pass').value.length>7 || document.getElementById('my_new_pass').value.length==0) {
		document.getElementById('frmuseredit').submit(); return false;
	} 
	else {
		alert("Das Passwort muss min. 8 Zeichen enthalten");
	}
}

</script>