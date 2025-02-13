<?php
/**
 * showpreview
 * @author stefan@covi.de
 * @since 3.3
 * @version 6.11
 * 
 * 2023-01-08
 * minor deprecation fixes
 * preview request changed to curl if available
 * 
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */

// second includes --------------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes, e.g. parser files ------
require ("./data/include/menuparser.inc.php");
require ("./data/include/fileparser.inc.php");
require ("./data/include/clsinterpreter.inc.php");
// head der datei

$parseTime = microtime();

if (isset($_REQUEST['previewid'])):
	$previewid = intval($_REQUEST['previewid']);
	$_SESSION['preview'] = true;
else:
	$previewid = 0;
	$_SESSION['preview'] = false;
endif;
if (isset($_REQUEST['previewlang'])):
	$previewlang = trim($_REQUEST['previewlang']);
	$_SESSION['previewlang'] = $previewlang;
else:
	$previewlang = 'de';
	$_SESSION['previewlang'] = 'de';
endif;

publishSites($previewid, 'preview', $previewlang);

$curl = true;

if (_isCurl()) {
    $defaults = array( 
        CURLOPT_URL => trim($_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/previewfile.php"), 
        CURLOPT_HEADER => 0, 
        CURLOPT_RETURNTRANSFER => TRUE, 
        CURLOPT_TIMEOUT => 4 
    );
    $ch = curl_init();
    curl_setopt_array($ch, $defaults);
    if (!($data = curl_exec($ch))) { $curl = false; } 
    curl_close($ch);
}

if ($curl) {
    echo $data;
}
else {
    if (!(@readfile($_SERVER['REQUEST_SCHEME']."://".$_SESSION['wspvars']['workspaceurl']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/previewfile.php"))) {
        readfile($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/previewfile.php");
    }
}

$_SESSION['preview'] = false;

echo "<div style=\"position: fixed; bottom: 0px; left: 0px; width: 99%; background: rgba(200,200,200,0.9); color: #000; font-size: 10px; padding: 5px 0.5%; font-family: 'Open Sans', sans-serif;\">";
echo "<span style=\"margin: 5px;\">Parsing time: ";
echo (floatval(microtime())-floatval($parseTime));
echo " seconds - returnInterpreterPath(): ";
echo returnInterpreterPath(intval($previewid), $previewlang);
echo (isset($_SESSION['previewforward']) && !empty($_SESSION['previewforward'])) ? " » ".trim($_SESSION['previewforward']) : ''; 
echo "</span></div>";

// EOF