<?php
/**
 * ajax-function to delete contents
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

    if (isset($_REQUEST['cid'])) {
        $cid = intval($_REQUEST['cid']);
        $sql = "UPDATE `content` SET `visibility` = 0, `trash` = 1, `lastchange` = ".time()." WHERE `cid` = ".intval($cid);
        $res = getAffSQL($sql);
        if ($res>0) {
            $m_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($cid);
            $m_res = doResultSQL($m_sql);
            if ($m_res>0) {
                $sql = "UPDATE `menu` SET `contentchanged` = ".contentChangeStat(intval($m_res),'content')." WHERE `mid` = ".intval($m_res);
                getAffSQL($sql);
            }
        }
		echo "#".$cid;
    }
}

// EOF