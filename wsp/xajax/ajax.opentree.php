<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-03-14
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
    if (isset($_REQUEST['action']) && isset($_REQUEST['mid'])) {
        if (!(isset($_SESSION['wspvars']['opentree']))) {
            $_SESSION['wspvars']['opentree'] = array();
        }
        if (trim($_REQUEST['action'])=='open') {
            $_SESSION['wspvars']['opentree'][] = intval($_REQUEST['mid']);
            $_SESSION['wspvars']['opentree'] = array_values(array_unique($_SESSION['wspvars']['opentree']));
        }
        else if (trim($_REQUEST['action'])=='close') {
            $_SESSION['wspvars']['opentree'] = array_values(array_unique(array_diff($_SESSION['wspvars']['opentree'], array(intval($_REQUEST['mid'])))));
        }
        else if (trim($_REQUEST['action'])=='openall') {
            $set = getResultSQL("SELECT `mid` FROM `menu` WHERE `trash` = 0");
            $_SESSION['wspvars']['opentree'] = $set;
        }
        else if (trim($_REQUEST['action'])=='closeall') {
            $_SESSION['wspvars']['opentree'] = array();
        }
        var_export($_SESSION['wspvars']['opentree']);
    }
} else {
    echo "<pre>no direct access allowed</pre>";
}
?>
