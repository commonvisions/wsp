<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8.4
 * @lastchange 2019-10-16
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    $wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));
	require $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	if (file_exists($wspdir."/data/include/ftpaccess.inc.php")) require $wspdir."/data/include/ftpaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

    $result = array('success' => false, 'id' => intval($_POST['dirid']), 'msg' => 'could not handle request');
    
    if (intval($_POST['dirid'])>0) {
        $finaldir = str_replace("//", "/", str_replace("//", "/", $_SESSION['fullstructure'][intval($_POST['dirid'])]['folder']));
        // do ftp login
        $ftp = doFTP();
        if ($ftp) {
            // check for files in folder
            $foldercontent = @ftp_nlist($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$finaldir)));
            if ($foldercontent && count($foldercontent)==0) {
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
        } else {
            $dir = $_SERVER['DOCUMENT_ROOT'] . '/' . ($_SESSION['wspvars']['wspbasediradd'] ?? '') . '/' . $finaldir;
            if (!is_dir($dir)) {
                $result['msg'] = "dir doesnt exist";
            } else {
                foreach (scandir($dir) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $filePath = $dir . '/' . $file ;
                    if (is_dir($filePath)) {
                        $result['msg'] = "please remove subdirectories of directory first";
                    } else {
                        unlink($filePath);
                    }
                }
                if (rmdir($dir)) {
                    doSQL("DELETE FROM `wspmedia` WHERE `mediafolder` = '" . escapeSQL($finaldir) . "'");
                    $result['success'] = true;
                    $result['msg'] = 'direct removal';
                } else {
                    $result['msg'] = $result['msg'] ?? "could not delete directory for unknown reason";
                }
            }
        }
    }
    echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
}
