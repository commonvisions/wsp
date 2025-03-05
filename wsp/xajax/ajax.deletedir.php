<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-10-16
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
    require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
    require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
    require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
    require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
    require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
    include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
    include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

    $result = array('success' => false, 'id' => intval($_POST['dirid']), 'msg' => 'could not handle request');
    
    if (intval($_POST['dirid'])>0) {

        $finaldir = str_replace("//", "/", str_replace("//", "/", $_SESSION['fullstructure'][intval($_POST['dirid'])]['folder']));
        // do ftp login
        $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
        if ($ftp) {
            // check for files in folder
            $foldercontent = @ftp_nlist($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$finaldir)));
            if (is_array($foldercontent) && count($foldercontent)==0) {
                if (@ftp_rmdir($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$finaldir)))) {
                    doSQL("DELETE FROM `wspmedia` WHERE `mediafolder` = '".$finaldir."'");
                    $result['success'] = true;
                }
                else {
                    $result['msg'] = 'ftp-delete of directory not possible';
                }
            }
            else {
                $removefile = 0;
                if ($foldercontent && is_array($foldercontent)) {
                    foreach ($foldercontent AS $k => $file) {
                        if (@ftp_delete($ftp, str_replace("//", "/", str_replace("//", "/", "/".$file)))) {
                            $removefile++;
                        }
                    }
                }
                if ($foldercontent && $removefile==count($foldercontent)) {
                    if (@ftp_rmdir($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$finaldir)))) {
                        doSQL("DELETE FROM `wspmedia` WHERE `mediafolder` = '".$finaldir."'");
                        $result['success'] = true;
                    }
                    else {
                        $result['msg'] = 'ftp-delete of directory /'.$_SESSION['wspvars']['ftpbasedir'].'/'.$finaldir.' not possible';
                    }
                }
                else {
                    if ($foldercontent) {
                        $result['msg'] = (count($foldercontent)-$removefile)." files in folder could not be deleted";
                    } else {
                        $result['msg'] = "could not read folder for existing files";
                    }
                }
            }
            ftp_close($ftp);
        }
    }

    echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

}

// EOF ?>