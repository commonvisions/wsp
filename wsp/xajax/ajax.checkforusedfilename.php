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
    // if request is not empty
    if (isset($_REQUEST['newname']) && trim($_REQUEST['newname'])!=''):
        $newname = urlText(trim($_REQUEST['newname']));
        $fcheck_sql = "SELECT `filename` FROM `menu` WHERE `filename` LIKE '".trim($newname)."' AND `trash` = 0";
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
        if (trim($newname)==''):
            echo returnIntLang('menuedit name cannot be empty', false)."#;#file".date("ymdHis");
        elseif ($newname!=trim($_REQUEST['newname'])):
            echo returnIntLang('menuedit name changed to unix', false)."#;#".$newname;
        elseif (in_array(trim($_REQUEST['newname']),$reservednames)):
            echo "'".trim($_REQUEST['newname'])."'".returnIntLang('menuedit reserved name', false)."#;#".$newname;
        elseif ($fcheck_num>0):
            echo "'".trim($_REQUEST['newname'])."'".returnIntLang('menuedit double name', false)."#;#".$newname;
        endif;
    endif;
} else {
    echo "<pre>no direct access allowed</pre>";
}
?>