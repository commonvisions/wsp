<?php
/**
 * WSP Print Stylesheet
 * @author stefan@covi.de
 * @since 3.2.3
 * @version 6.7
 * @lastchange 2018-09-17
 */

// $wspvars['icon']['expand_x'] = "/expands.gif";
// $wspvars['icon']['collapse_x'] = "/collapses.gif";

header("Content-Type: text/css");

$wspvars['iconset']['folder'] = "";
$_SESSION['wspvars']['iconset']['folder'] = $wspvars['iconset']['folder'];

if (isset($styleinclude) && $styleinclude==false):

?>html {
	width: 100%;
	margin: 0px;
	padding: 0px;
	}
	
body {
	margin: 0px;
	padding: 0px;
	font-family: verdana, arial, sans-serif;
	font-size: 10px;
	background: #ffffff;
	}

h1 {
	font-size: 120%;
	font-weight: bold;
	margin-top: 4px;
	margin-bottom: 14px;
	color: #2f629e;
	}

h2 {
	font-size: 110%;
	font-weight: bold;
	margin-top: 8px;
	margin-bottom: 14px;
	color: #2f629e;
	}

p {
	margin-top: 4px;
	margin-bottom: 4px;
	}

#topholderback, #topholder, #menuholder, #dhtmltooltip, #topspacer, #msgbar, #infoholder {
	display: none;
	}

#contentarea fieldset {
	display: none;
	}

#contentarea fieldset.printview {
	display: block;
	border: none;
	padding-top: 3cm;
	page-break-after: always;
	}
	
#contentarea fieldset.printview.optional {
	display: none;
	}

#contentarea fieldset.printview table {
	border-collapse: collapse;
	}
	
#contentarea fieldset.printview table td {
	border: 1px solid black;
	}

#contentarea fieldset.printview div.barcode {
	display: block;
	width: 100%;
	height: 40px;
	}
	
<?php endif; ?>

/* EOF */