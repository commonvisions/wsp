<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-03-10
 */

session_start();
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {

    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    
    if (isset($_POST['repass']) && trim($_POST['repass'])!='' && isset($_POST['reuser']) && trim($_POST['reuser'])!='') {
        $relogin_sql = "SELECT `userid` FROM `security` WHERE `usevar` = '".escapeSQL(trim($_POST['reuser']))."'";
        $relogin_res = doResultSQL($relogin_sql);
        if (intval($relogin_res)>0) {
            $_SESSION['wspvars']['userid'] = intval($relogin_res);
        }
        if (isset($_SESSION['wspvars']['userid']) && intval($_SESSION['wspvars']['userid'])>0) {
            $login_sql = "SELECT * FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid'])." AND `pass` = '".md5($_POST['repass'])."' AND `usertype` != '0'";
            $login_res = doSQL($login_sql);
            if ($login_res['num']==1) {
                // login allowed
                if (isset($_SESSION['wspvars']['lockedvar'])) {
                    $_SESSION['wspvars']['usevar'] = $_SESSION['wspvars']['lockedvar'];
                } else {
                    $_SESSION['wspvars']['usevar'] = trim($_POST['reuser']);
                }
                // if some connection is avaiable
                if ((isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) || (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false)) {
                    $createfolder = createFolder('/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar']);
                    if ($createfolder) {
                        unset($_SESSION['wspvars']['lockedvar']);
                        doSQL("UPDATE `security` SET `timevar` = ".time()." WHERE `usevar` = '".$_SESSION['wspvars']['usevar']."'");
                        $_SESSION['wspvars']['lockscreen'] = false;
                        echo 'true';
                    } else {
                        // tmp folder could not be created so we cant do anything
                        echo (defined('WSP_DEV') && WSP_DEV) ? 'connection exists but temporary folder cannot be created' : 'false';
                    }
                } else {
                    echo (defined('WSP_DEV') && WSP_DEV) ? 'no connection to srv avaiable' : 'false';
                }
            }
            else {
                // login not allowed
                echo (defined('WSP_DEV') && WSP_DEV) ? 'login_num != 1' : 'false';
            }
        }
        else {
            echo (defined('WSP_DEV') && WSP_DEV) ? 'session_wspvars_userid not set or empty' : 'false';
        }
    }
    else {
        echo (defined('WSP_DEV') && WSP_DEV) ? 'post_repass ('.trim($_POST['repass']).') or post_reuser ('.trim($_POST['reuser']).') not set or empty' : 'false';
    }
}
else {
    echo "<pre>No direct access allowed</pre>";
}

// EOF ?>