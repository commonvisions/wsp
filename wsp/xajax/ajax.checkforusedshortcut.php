<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-06-17
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
    // if request is not empty
    if (isset($_REQUEST['newshortcut']) && trim($_REQUEST['newshortcut'])!='') {
        $newshortcut = urlText(trim($_REQUEST['newshortcut']));
        $fcheck_sql = "SELECT `filename` FROM `menu` WHERE `linktoshortcut` LIKE '".escapeSQL(trim($newshortcut))."' AND `trash` = 0";
        $fcheck_res = doSQL($fcheck_sql);
        $fcheck_num = 0;
        // list of reserved names
        $reservednames = array('index','wsp','data','media');
        if (is_array($_SESSION['wspvars']) && array_key_exists('publisherdata',$_SESSION['wspvars']) && array_key_exists('nonames',$_SESSION['wspvars']['publisherdata']) && trim($_SESSION['wspvars']['publisherdata']['nonames'])!=''):
            $nonames = explode(";",str_replace(";;", ";", str_replace(";;", ";", str_replace("\r", ";", str_replace("\n", ";", $_SESSION['wspvars']['publisherdata']['nonames'])))));
            if (is_array($nonames)):
                $reservednames = array_merge($reservednames,$nonames);
            endif;
        endif;
        $reservednames = array_unique($reservednames);
        if (trim($newshortcut)=='') {
            echo returnIntLang('menuedit name cannot be empty', false)."#;#file".date("ymdHis");
        }
        else if ($newshortcut!=trim($_REQUEST['newshortcut'])) {
            echo returnIntLang('menuedit shortcut changed to unix', false)."#;#".$newshortcut;
        }
        else if (in_array(trim($_REQUEST['newshortcut']),$reservednames)) {
            echo "'".trim($_REQUEST['newshortcut'])."'".returnIntLang('menuedit reserved shortcut', false)."#;#";
        }
        else if ($fcheck_res['num']>0) {
            echo "'".trim($_REQUEST['newshortcut'])."'".returnIntLang('menuedit double shortcut', false)."#;#";
        }
    }
}
?>