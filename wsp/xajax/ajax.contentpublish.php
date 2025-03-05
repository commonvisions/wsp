<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-08-20
 */

session_start();

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    
    require("../data/include/globalvars.inc.php");
    require("../data/include/siteinfo.inc.php");
    require("../data/include/clsinterpreter.inc.php");

    if (isset($_POST['mid']) && intval($_POST['mid'])>0) {
        // check for selected page if mid is already in publisher
        $fp_sql = "SELECT `id` FROM `wspqueue` WHERE (`action` = 'publishcontent' OR `action` = 'publishitem' OR `action` = 'publishstructure') AND `param` = ".intval($_POST['mid'])." AND `done` = 0";
        $fp_res = doSQL($fp_sql);
        if ($fp_res['num']==0) {
            $wi_res = doSQL("INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = ".time().", `action` = 'publishitem', `param` = '".intval($_POST['mid'])."', `timeout` = ".time());
                addWSPMsg('errormsg', var_export($wi_res, true));
            $wq_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = 0"; 
            $wq_res = doSQL($wq_sql);
                addWSPMsg('errormsg', var_export($wq_res, true));
            echo $wq_res['num'];
        } else {
            echo -1;
        }
    } else {
        echo "missingdata|false";
    }
}
else {
	echo "timeout|false";
}

// EOF ?>