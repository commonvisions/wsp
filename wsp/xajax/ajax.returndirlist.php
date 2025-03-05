<?php
/**
 * returning list of media folders
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-07-31
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
    if (isset($_REQUEST['path']) && trim($_REQUEST['path'])!='' && cleanPath($_REQUEST['path'])==$_REQUEST['path'] && isset($_SESSION['wspvars']['usevar']) && trim($_SESSION['wspvars']['usevar'])!='') {
        
        $tmplist = explode(DIRECTORY_SEPARATOR, trim(cleanPath($_REQUEST['path'])));
        $list = '';
        foreach ($tmplist AS $tlk => $tlv) {
            if (trim($list)=='' && trim($tlv)!='' && trim($tlv)!='media') {
                $list = trim($tlv);
            }
        }
        $medialist = cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/".$list.".json");
        if (file_exists($medialist)) {
            // read existing structure file ...
            header('Content-Type: application/json');
            readfile($medialist);
        }
        else {
            $dirlist = array(
                array(
                    'id' => 'root',
                    'text' => returnIntLang('str basedir', false)." <span class='badge inline-badge'>".count(scanfiles(cleanPath($_SESSION['wspvars']['upload']['basetarget'])))."</span>",
                    'a_attr' => array(
                        'rel' => base64_encode(cleanPath("/".$_SESSION['wspvars']['upload']['basetarget']."/")),
                        'onclick' => 'showFiles($(this).attr("rel"))',
                    ),
                    'state' => array(
                        'selected' => false,
                    ),
                ),
            );
            $subdirlist = dirList(cleanPath($_REQUEST['path']), substr(cleanPath($_REQUEST['path']),1), true, true, true, $list);
            if (is_array($subdirlist)): $dirlist = array_merge($dirlist, $subdirlist); endif;

            // create tmp user directory (again) if it doesnt exist 
            if (!(is_dir(cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar'])))) {
                createFolder("/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']);
            }
            $handle = fopen($medialist, "w");
            fwrite($handle, json_encode($dirlist));
            fclose($handle);
        
            header('Content-Type: application/json');
            echo json_encode($dirlist, true);
        }
    }
    else {
        echo "<pre>It seems no login is accessable</pre>";
    }
}
else {
    echo "<pre>No direct access allowed</pre>";
}

// EOF