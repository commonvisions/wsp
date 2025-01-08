<?php
/**
 * Autocomplete Funktionen
 * @author COVI
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2018-09-20
 */

$GLOBALS['autocompleteList'] = array();

function ordnerinhalt($ordner='.'){
	$content = "";

	$handle = opendir($ordner);
	while ($file = readdir ($handle)):
		if ($file{0} != '.'): //Versteckte Dateien nicht anzeigen
			if (is_dir($ordner.'/'.$file)):
				$folderArray[] = $file;
			else:
				$fileArray[] = $file;
			endif;
		endif;
	endwhile;
	closedir($handle);
	//Erst die Ordner ausgeben
	if (isset($folderArray) && $folderArray != 'thumbs'):
		asort($folderArray);
		foreach ($folderArray as $row):
			ordnerinhalt($ordner.'/'.$row); //rekursive Funktion
		endforeach;
	endif;
	//Dann die Dateien ausgeben
	if (isset($fileArray)):
		asort($fileArray);
		foreach ($fileArray as $row):
			$GLOBALS['autocompleteList'][] = $ordner.'/'.$row;
		endforeach;
	endif;
}
function imageAutoComplete($path = ".", $inputId = 'autocompleteinput', $divId = 'autocompletediv'){
	ordnerinhalt($path);
	$GLOBALS['autocompleteList'] = implode ("\",\n\"",$GLOBALS['autocompleteList']);

	$script = '<div id="' . $divId . '" class="autocomplete" style="display: none;"></div>'."\n";
	$script.= '<script type="text/javascript" language="javascript" charset="utf-8">'."\n";
	$script.= '<!--'."\n";
	$script.= '// <![CDATA['."\n";
	$script.= "new Autocompleter.Local('" . $inputId . "','" . $divId . "',\n";
	$script.= 'new Array("' . $GLOBALS['autocompleteList'] . '"), { tokens: new Array(), fullSearch: true, partialSearch: true, ignoreCase: false });'."\n";
	$script.= '// ]]>'."\n";
	$script.= '-->'."\n";
	$script.= '</script>'."\n";
	return $script;
}
// EOF ?>