<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2021-06-02
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");

    if (isset($_REQUEST['newfilename']) && trim($_REQUEST['newfilename'])!='' && isset($_REQUEST['orgfilename']) && trim($_REQUEST['orgfilename'])!='') {
        if (trim($_REQUEST['newfilename'])!=trim($_REQUEST['orgfilename'])) {
            $newfilename = urlText(trim($_REQUEST['newfilename']));
            $f_sql = "SELECT `mid` FROM `wspmedia` WHERE `filepath` = '".escapeSQL($newfilename)."'";
            $f_res = doResultSQL($f_sql);
            if ($f_res!==false) {
                $newfilename = $newfilename."-".time();
            }
            echo $newfilename;
        }
    } else {
        echo '';
    }
} else {
    echo '';
}

// EOF