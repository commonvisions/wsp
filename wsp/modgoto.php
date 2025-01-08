<?php
/**
 * WSP-Modul-jumper
 * @author COVI
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 3.2
 * @version 6.8
 * @lastchange 2019-01-19
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
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

// EOF ?>