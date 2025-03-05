<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");

    if (isset($_REQUEST['cid'])) {
        $cid_sql = "SELECT `visibility`, `mid` FROM `content` WHERE `cid` = ".intval($_REQUEST['cid']);
        $cid_res = doSQL($cid_sql);
        $vis = 0; $mid = 0;
        if ($cid_res['num']>0) {
            $vis = intval($cid_res['set'][0]['visibility']); 
            $mid = intval($cid_res['set'][0]['mid']);
        }
        if ($vis==0) {
            doSQL("UPDATE `content` SET `visibility` = 1, `lastchange` = ".time()." WHERE `cid` = ".intval($_REQUEST['cid']));
            echo "show";
        }
        else {
            doSQL("UPDATE `content` SET `visibility` = 0, `lastchange` = ".time()." WHERE `cid` = ".intval($_REQUEST['cid']));
            echo "hide";
        }
        /* updating menu for changed content */
        if($mid>0) {
            $sql = "UPDATE `menu` SET `contentchanged` = ".contentChangeStat(intval($mid),'content')." WHERE `mid` = ".intval($mid);
            getAffSQL($sql);
            
        }
    }
}
?>