<?php
/**
 *
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-07
 */

define('DEVNOTE', '<p><strong style="color: rgb(139, 0, 0);">Diese Seite befindet sich noch in Entwicklung.</strong></p>');
define('WSP_VERSION', '7.0');
define('WSP_LONGVERSION', 'WSP 7.0');

// some base vars replaced in every head if needed
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['pagedesc'] = array('fa fa-spinner fa-spin',returnIntLang('Category undefined'),returnIntLang('Page undefined'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array();
$_SESSION['wspvars']['addpagejs'] = array();

// rechtebereiche, die verwaltet werden koennen
// rechtemoeglichkeiten, die die rechtebereiche annehmen koennen
// yes|no|auswahl, mit yes = 1, no = 0 und auswahl = 2
// possible modes: 
// 1 = yes/no create and edit
// 2 = choose from structure and create and edit
// 3 = yes/no edit existing facts
// 4 = extends 2 - choose from structure and edit existing
// 5 = 
// 6 = filesystem folder
$_SESSION['wspvars']['posright'] = array(
	'siteprops' => array( 'mode' => 1 ),
	'sitestructure' => array( 'mode' => 7 ), // site structure
	'contents' => array( 'mode' => 15 ), // contents
	'publisher' => array( 'mode' => 12 ), // publisher
	'rss' => array( 'mode' => 1 ), // rss-feature
	'design' => array( 'mode' => 1 ), // css, screen images, templates, variables
	'imagesfolder' => array( 'mode' => 6, 'basefolder' => '/media/images/' ),
	'downloadfolder' => array( 'mode' => 6, 'basefolder' => '/media/download/' ),
	'flashfolder' => array( 'mode' => 6, 'basefolder' => '/media/flash/' )
	);

// 2017-08-25
// OLD $wspvars['rightabilities'] » NEW first level keys from $_WSPVARS['posright'];
// OLD $wspvars['rightpossibilities'] » NEW values from $_WSPVARS['posright'][each key]['mode'];
// OLD $wspvars['rightabilityarray'] » NEW $_WSPVARS['posright'];

// directories to accept writing while installing modules
$_SESSION['wspvars']['allowdir'] = array("data/menu","data/modules","media/flash","media/images","media/screen","media/layout","media/download","[wsp]/data/interpreter","[wsp]/data/lang","[wsp]/data/modsetup","[wsp]/data/modules","[wsp]/media/javascript","[wsp]/media/screen");

?>