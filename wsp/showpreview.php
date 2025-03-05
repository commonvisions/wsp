<?php
/**
 * showpreview
 * @author stefan@covi.de
 * @since 3.3
 * @version 7.0
 * @lastchange 2021-06-02
 */

// start session ----------------------------------
session_start();
// base includes ----------------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// checkParamVar ----------------------------------

// define actual system position ------------------

// second includes --------------------------------
// require ("./data/include/checkuser.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes, e.g. parser files ------
require ("./data/include/menuparser.inc.php");
require ("./data/include/fileparser.inc.php");
require ("./data/include/clsinterpreter.inc.php");
// head der datei ---------------------------------

$_SESSION['wspvars']['publisherdata'] = getWSPProperties();

if(!(isset($_SESSION['wspvars']['userid'])) || (isset($_SESSION['wspvars']['userid']) && intval($_SESSION['wspvars']['userid'])==0)):
    if (isset($_REQUEST['previewid'])): unset($_REQUEST['previewid']); endif;
    if (isset($_REQUEST['previewlang'])): unset($_REQUEST['previewlang']); endif;
endif;

if (isset($_REQUEST['show']) && trim($_REQUEST['show'])!='') {
    $showdata = explode(":", cryptRootPhrase( $_REQUEST['show'], 'd' ));
    if (isset($showdata[2]) && intval($showdata[2])>time()-86400) {
        if (isset($showdata[0]) && intval($showdata[0])>0):
            $_REQUEST['previewid'] = intval($showdata[0]);
        endif;
        if (isset($showdata[1]) && trim($showdata[1])!=''):
            $_REQUEST['previewlang'] = trim($showdata[1]);
        endif;
        // create local directory for external request
        $_SESSION['wspvars']['usevar'] = md5(mt_rand());
        if (!(isset($_SESSION['wspvars']['ftp'])) || $_SESSION['wspvars']['ftp']!==true) {
            $_SESSION['wspvars']['ftp_host'] = trim(FTP_HOST);
            $_SESSION['wspvars']['ftp_user'] = trim(FTP_USER);
            $_SESSION['wspvars']['ftp_pass'] = trim(FTP_PASS);
            $_SESSION['wspvars']['ftp_base'] = trim(FTP_BASE);
            $_SESSION['wspvars']['ftp_port'] = (defined('FTP_PORT')?intval(FTP_PORT):21);
            $_SESSION['wspvars']['ftp_ssl'] = (defined('FTP_SSL')?FTP_SSL:false);
            $_SESSION['wspvars']['ftp_pasv'] = (defined('FTP_PASV')?FTP_PASV:false);
            $_SESSION['wspvars']['ftp'] = true;
        }
        $ftp = doFTP();
        $mkdir = ftp_mkdir($ftp, FTP_BASE.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar']);
        if ($mkdir) {
            $chmod = ftp_chmod($ftp, 0777, $mkdir);
            @ftp_close($ftp);
        }
    }
}

if (isset($_REQUEST['previewid'])):
	$_SESSION['wspvars']['previewid'] = intval($_REQUEST['previewid']);
	$preview = true;
else:
	$_SESSION['wspvars']['previewid'] = 0;
	$preview = false;
endif;

if (isset($_REQUEST['previewlang'])):
	$_SESSION['wspvars']['previewlang'] = trim($_REQUEST['previewlang']);
else:
    $_SESSION['wspvars']['previewlang'] = WSP_LANG;
endif;

if ($preview) {
    $previewlink = "<a href='/".WSP_DIR."/showpreview.php?show=".cryptRootPhrase($_SESSION['wspvars']['previewid'].":".$_SESSION['wspvars']['previewlang'].":".time(),'e')."'>Right Click to Copy</a>";
    publishSites($_SESSION['wspvars']['previewid'], 'preview', $_SESSION['wspvars']['previewlang']);
}

include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");

if ($preview) {
    echo '<iframe width="100%" height="99%" style="padding-top: 55px; border: none; width: 100vw; height: 99vh;" src="/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/previewfile.php?'.time().'"></iframe>';
}
else {
    echo '<div style="width: 100vw; height: 100vh; display: table-cell; vertical-align: middle; text-align: center;"><p style="font-size: 6em;"><i class="fa fa-meh-o"></i></p><p>'.returnIntLang('preview link has expired or is false', false).'</p></div>';
}

?>
</body>
</html>