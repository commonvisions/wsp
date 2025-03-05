<?php
/**
 * @description module download
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-12
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$modules_sql = "SELECT * FROM `modules` WHERE `guid` = '".escapeSQL(base64_decode($_REQUEST['mk']))."' ORDER BY `name`";
$modules_res = doSQL($modules_sql);
/* define page specific functions ----------------- */
if ($modules_res['num']>0) {
    
    /*
    if (trim($modules_res['set'][0]['archive'])!='') {
        $orgzip = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/modules/'.trim($modules_res['set'][0]['archive']));
        // make copy of original zip to tmp folder
        $ftp = doFTP();
        if ($ftp!==false) {
            ftp_put($ftp, FTP_BASE."/".WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/'.trim(str_replace(".zip", "-".time().".zip", basename(trim($modules_res['set'][0]['archive'])))), $orgzip, FTP_BINARY);
            ftp_close($ftp);
        }
    }
    */
    
    $zipfile = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/'.trim(str_replace(".zip", "-".time().".zip", basename(trim($modules_res['set'][0]['archive'])))));

    //  if (is_file($zipfile)) {
        $filelist = unserializeBroken($modules_res['set'][0]['filelist']);    
        if (!($filelist) && trim($modules_res['set'][0]['filelist'])!='') {
            $filelist = explode(PHP_EOL, trim($modules_res['set'][0]['filelist']));
        }
    
        $zip = new ZipArchive;
        if ($zip->open($zipfile, ZipArchive::CREATE)===true) {
            if (is_array($filelist)) {
                foreach($filelist AS $flk => $flv) {
                    if (substr(trim($flv),-4)=='.sql') {
                        // use modules database sql
                    }
                    else if (trim($flv)=='/database.xml') {
                        // use modules database xml
                    }
                    else if (trim($flv)=='/setup.php') {
                        // use modules setup
                    }
                    else {
                        if (substr(trim($flv),0,5)=='/wsp/') {
                            @$zip->deleteName(cleanPath('/_wsp_/'.substr(trim($flv),5)));
                            $zip->addFile(cleanPath(DOCUMENT_ROOT.'/'.trim($flv)), cleanPath('/_wsp_/'.substr(trim($flv),5)));
                        }
                        else {
                            @$zip->deleteName(cleanPath(trim($flv)));
                            $zip->addFile(cleanPath(DOCUMENT_ROOT.'/'.trim($flv)), cleanPath(trim($flv)));
                        }
                    }
                }
            }
            $zip->close();
        } else {
            echo 'Fehler';
        }
//      }
    
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($zipfile).'"');
    header("Content-length: " . filesize($zipfile));
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile($zipfile);
    unlink($zipfile);

} 

// EOF