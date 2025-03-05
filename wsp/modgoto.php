<?php
/**
 * WSP-Modul-jumper
 * @author stefan@covi.de
 * @since 3.2
 * @version 7.0
 * @lastchange 2019-01-09
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['mgroup'] = 20;
$_SESSION['wspvars']['lockstat'] = 'modgoto';
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes */

if (isset($_REQUEST['modguid'])) {
    
    $mod_sql = "SELECT `id`, `title` FROM `wspmenu` WHERE `guid` = '".escapeSQL(trim($_REQUEST['modguid']))."'";
    $mod_res = doSQL($mod_sql);
    if ($mod_res['num']>0) {
        unset($_REQUEST['modguid']);
        $_SESSION['modid'] = intval($mod_res['set'][0]['id']);
        $_SESSION['modgotoparam'] = $_REQUEST;
    } 
    
}
else if (isset($_REQUEST['modid'])) {
    $_SESSION['modid'] = intval($_REQUEST['modid']);
}

header("location: modinterpreter.php");
die();

// EOF ?>