<?php
/**
 * global language file
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2018-09-18
 */

if (isset($_REQUEST['setlang'])):
	$_SESSION['wspvars']['locallang'] = trim($_REQUEST['setlang']);
endif;

$_SESSION['wspvars']['locallanguages'] = array();
if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/lang/")):
	$d = @dir(str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/lang/"));
	while (false !== ($entry = $d->read())):
		if ((substr($entry, 0, 1)!='.') && (is_file(str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/lang/".$entry))) && substr($entry, -3)=='php'):
			$_SESSION['wspvars']['locallanguages'][] = $entry;
		endif;
	endwhile;
	$d->close();
endif;

if (is_array($_SESSION['wspvars']['locallanguages'])):
	foreach ($_SESSION['wspvars']['locallanguages'] AS $langkey => $langvalue):
		include_once(str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/lang/".$langvalue));
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
		if (array_key_exists('lang', $GLOBALS) && is_array($GLOBALS['lang'][($_SESSION['wspvars']['locallang'])])):
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

if (!function_exists('helpText')):
	function helpText($helptext, $returnecho = true) {
		if ($returnecho):
			echo "<accr onMouseover=\"ddrivetip('".addslashes($helptext)."');\" onMouseout=\"hideddrivetip();\"><span class=\"bubblemessage orange\">".returnIntLang('bubble info', false)."</span></accr>";
		else:
			return "<accr onMouseover=\"ddrivetip('".addslashes($helptext)."');\" onMouseout=\"hideddrivetip();\"><span class=\"bubblemessage orange\">".returnIntLang('bubble info', false)."</span></accr>";
		endif;
		}
endif;

// EOF ?>