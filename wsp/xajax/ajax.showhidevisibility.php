<?php
/**
 * sichtbarkeit von menüpunkten
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
    
    if (isset($_REQUEST['mid']) && intval($_REQUEST['mid'])>0 && isset($_REQUEST['vis'])) {
        $res = doSQL("UPDATE `menu` SET `visibility` = ".intval($_REQUEST['vis'])." WHERE `mid` = ".intval($_REQUEST['mid']));
        if ($res['aff']==1) {
            // has to be developed: get all affected pages and set contentchange
            // use: contentChangeStat(intval($sAv['id']), 'structure')
            // status 2019-03-14: update ALL pages contentchange
            doSQL("UPDATE `menu` SET `contentchange` = 1 WHERE `trash` = 0");
            echo true;
        }
    }
    
}

// EOF ?>