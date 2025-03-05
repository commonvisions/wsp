<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2020-06-04
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
    
    if (is_array($_FILES["file"])) {
        if ($_FILES["file"]['tmp_name']!='' && $_FILES["file"]['error']==0) {
            // file was uploaded to tmp
            $sysfilename = basename($_FILES["file"]['name']);
            $sysfolder = base64_decode($_POST['fldr']);
            $sysfolder2 = $_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']];
            $tmppath = cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR.$sysfilename);
            // copy file to tmp folder
            $localcopy = move_uploaded_file($_FILES["file"]['tmp_name'], $tmppath);
            if ($localcopy===false) {
                addWSPMsg('errormsg', returnIntLang('media could not copy uploaded file to tmp'));
                $error = true;
            }
            else {
                // file is copied with it's name to tmp so we ftp-copy it to final destination
                $ftp = doFTP();
                if ($ftp!==false) {
                    if (ftp_put($ftp, cleanPath(FTP_BASE.DIRECTORY_SEPARATOR.$sysfolder.$sysfilename), $tmppath, FTP_BINARY)) {
                        addWSPMsg('noticemsg', returnIntLang('media file uploaded1').$sysfilename.returnIntLang('media file uploaded2').$sysfolder.returnIntLang('media file uploaded3'));
                        $error = false;
                    } else {
                        addWSPMsg('errormsg', returnIntLang('media could not copy to ftp'));
                        $error = true;
                    }
                    ftp_close($ftp);
                } else {
                    addWSPMsg('errormsg', returnIntLang('media could not connect to ftp'));
                    $error = true;
                }
            }
        }
    }

    // Remember to process the uploads!

    $f = $_FILES["file"];
    $file = $f["name"];

    $chunk  = $_POST["chunk"];
    $chunks = $_POST["chunks"];

    if ($error) {
        die("Error: " . $error);
    } else {
        die("Chunk: " . ($chunk + 1) . " of " . $chunks);
    }

}
else {
    echo "<pre>No direct access allowed</pre>";
}

?>