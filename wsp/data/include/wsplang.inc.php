<?php
/**
 * global language controller
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-06-02
 */

if (isset($_REQUEST['setlang']) && trim($_REQUEST['setlang'])) {
	$_SESSION['wspvars']['locallang'] = trim(filter_var($_REQUEST['setlang'], FILTER_SANITIZE_URL));
}

$_SESSION['wspvars']['locallanguages'] = array();
if (is_dir(str_replace("//", "/", DOCUMENT_ROOT."/".WSP_DIR."/data/lang/"))):
	$d = @dir(str_replace("//", "/", DOCUMENT_ROOT."/".WSP_DIR."/data/lang/"));
	while (false !== ($entry = $d->read())):
		if ((substr($entry, 0, 1)!='.') && (is_file(str_replace("//", "/", DOCUMENT_ROOT."/".WSP_DIR."/data/lang/".$entry))) && substr($entry, -3)=='php'):
			$_SESSION['wspvars']['locallanguages'][] = $entry;
		endif;
	endwhile;
	$d->close();
endif;

if (is_array($_SESSION['wspvars']['locallanguages'])):
	foreach ($_SESSION['wspvars']['locallanguages'] AS $langkey => $langvalue):
		include_once(str_replace("//", "/", DOCUMENT_ROOT."/".WSP_DIR."/data/lang/".$langvalue));
	endforeach;
	foreach ($_SESSION['wspvars']['locallanguages'] AS $langkey => $langvalue):
		if ($langkey==intval($langkey)):
			unset($_SESSION['wspvars']['locallanguages'][intval($langkey)]);
		endif;
	endforeach;
endif;

if (!function_exists('returnIntLang')):
	function returnIntLang($internationalize, $textoutput = true) {
		if (!(isset($_SESSION['wspvars']['locallang'])) || $_SESSION['wspvars']['locallang'] == ''):
			$_SESSION['wspvars']['locallang'] = 'de';
		endif;
		if (array_key_exists('lang', $GLOBALS) && isset($GLOBALS['lang'][($_SESSION['wspvars']['locallang'])]) && is_array($GLOBALS['lang'][($_SESSION['wspvars']['locallang'])])):
			if (array_key_exists($internationalize, $GLOBALS['lang'][($_SESSION['wspvars']['locallang'])])):
				return setUTF8($GLOBALS['lang'][($_SESSION['wspvars']['locallang'])][$internationalize]);
			else:
				if ($textoutput):
					return "<em style=\"color: red;\">".$internationalize." [".$_SESSION['wspvars']['locallang']."]</em>";
				else:
					return setUTF8($internationalize." [".$_SESSION['wspvars']['locallang']."]");
				endif;
			endif;
		else:
			if ($textoutput):
				return "<em style=\"color: red;\">[".$_SESSION['wspvars']['locallang']."] not installed</em>";
			else:
				return "[".$_SESSION['wspvars']['locallang']."] not installed";
			endif;
		endif;
		}
endif;

?>