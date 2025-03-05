<?php
/**
 * Verwaltung von Dateien
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-07-08
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['mgroup'] = 99;
$_SESSION['wspvars']['lockstat'] = isset($_REQUEST['menuposition'])?trim($_REQUEST['menuposition']):'images';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-paint-brush',returnIntLang('menu design'),returnIntLang('edit media'));
$_SESSION['wspvars']['fposition'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    'dropify.css',
);
$_SESSION['wspvars']['addpagejs'] = array(
    'dropify.js',
);
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
$mediafolder = array(
    'images' => '/media/images/', 
    'screen' =>  '/media/screen/', 
    'download' => '/media/download/', 
    'video' => '/media/video/', 
    'fonts' => '/media/fonts/'
);
$medialist = array( 
    'images' => 'imagemanagement.php', 
    'screen' =>  'screenmanagement.php', 
    'download' => 'documentmanagement.php', 
    'video' => 'mediamanagement.php', 
    'fonts' => 'fontmanagement.php'
);
$thumbtypes = $previewtypes = array('png', 'jpg');
// return to last page if some data is missing
if (!(isset($_REQUEST['fl']))) {
	if (isset($_REQUEST['ml'])) {
        header('location: /'.WSP_DIR.'/'.$medialist[base64_decode($_REQUEST['ml'])]);
        die();
    }
    else {
		header('location: /'.WSP_DIR.'/imagemanagement.php');
        die();
	}
} else if (!(isset($_REQUEST['ml']))) {
    header('location: /'.WSP_DIR.'/imagemanagement.php');
    die();
} else {
    $fl = trim($_REQUEST['fl']);
    $flcheck = unserializeBroken(base64_decode($fl));
    if (strl_replace($mediafolder[base64_decode($_REQUEST['ml'])], "", $flcheck[1])==$flcheck[1]) {
        header('location: /'.WSP_DIR.'/index.php');
        die();
    }
}

// upload preview
if (isset($_POST['action']) && trim($_POST['action'])=='uploadthumb' && isset($_FILES) && isset($_FILES['uploadthumb']) && is_array($_FILES['uploadthumb']) && $_FILES['uploadthumb']['error']==0) {
    echo '<br /><br /><br /><br /><br /><br />';
    var_export($_POST);
    var_export($_FILES);

    $file = unserializeBroken(base64_decode($_POST['fl']));
    $upload['fullpath'] = cleanPath($file[1]);
    $upload['fullfile'] = basename($upload['fullpath']);


    $upload['mediatyp'] = base64_decode($_POST['ml']);
    $upload['mediafol'] = $mediafolder[base64_decode($_POST['ml'])];
    $upload['fullfold'] = trim(substr($upload['fullpath'],0,-(strlen($upload['fullfile']))));
    $upload['thmbfold'] = trim(substr($upload['fullpath'],0,-(strlen($upload['fullfile']))));
    // rename Upload to
    $upload['filename'] = substr($upload['fullfile'],0,strrpos($upload['fullfile'], '.'));

    var_export($upload);

}

// save/update information
if (isset($_POST['action']) && trim($_POST['action'])=='savefile') {
    $file = unserializeBroken(base64_decode($_REQUEST['fl']));
    $handle['fullpath'] = cleanPath($file[1]);
    $handle['fullfile'] = basename($handle['fullpath']);
    $handle['fullfold'] = trim(substr($handle['fullpath'],0,-(strlen($handle['fullfile']))));
    $handle['filetype'] = substr($handle['fullfile'],strrpos($handle['fullfile'], '.'));
    $handle['filename'] = substr($handle['fullfile'],0,strrpos($handle['fullfile'], '.'));
    $handle['savepath'] = $handle['fullpath'];
    if (strtolower(trim($_POST['filename']))!=strtolower(trim($_POST['origname'])) && trim(urltext($_POST['filename']))!='') {
        // update savepath-handler to store information with NEW filename
        $handle['savepath'] = cleanPath($handle['fullfold']."/".strtolower(trim(urltext($_POST['filename'])).$handle['filetype']));
        // rename file on server
        $ftp = ((defined('FTP_SSL') && FTP_SSL===true)?ftp_ssl_connect(FTP_HOST, intval(FTP_PORT)):ftp_connect(FTP_HOST, intval(FTP_PORT))); if ($ftp!==false) {if (!ftp_login($ftp, FTP_USER, FTP_PASS)) { $ftp = false; }} if (defined('FTP_PASV') && $ftp!==false) { ftp_pasv($ftp, FTP_PASV); }
        if ($ftp!==false) {
            // DO renaming and check for result
            if (!(@ftp_rename($ftp, cleanPath(FTP_BASE."/".$handle['fullpath']), cleanPath(FTP_BASE."/".$handle['savepath'])))) {
                $handle['savepath'] = $handle['fullpath'];
                addWSPMsg('errormsg', returnIntLang('mediadetails could not rename file'));
            } 
            else {
                // udpate $_REQUEST['fl'] with new contents
                $fl = base64_encode(serialize(array($_SESSION['wspvars']['upload']['basetarget'], $handle['savepath'])));
                echo "<pre>".var_export(unserializeBroken(base64_decode($fl)), true)."</pre>";
                echo "<pre>".var_export($handle, true)."</pre>";
                echo "<pre>".var_export($_REQUEST, true)."</pre>";
                $sql = "DELETE FROM `wspmedia` WHERE `filepath` = '".escapeSQL(trim($handle['fullpath']))."'";
				doSQL($sql);
                $sql = "INSERT INTO `wspmedia` SET `filepath` = '".escapeSQL(trim($handle['savepath']))."', `filekey` = '".escapeSQL(trim($fl))."'";
				doSQL($sql);
            }
        } else {
            addWSPMsg('errormsg', returnIntLang('mediadetails could not rename file'));
        }
    }
    // remove older stored data in mediadesc-table
    doSQL("DELETE FROM `mediadesc` WHERE `mediafile` = '".escapeSQL(trim($handle['fullpath']))."'");
}

// get details if not redirected
$details_sql = "SELECT * FROM `wspmedia` WHERE `filekey` = '".escapeSQL(trim($fl))."' ORDER BY `mid` DESC LIMIT 0,1";
$details_res = doSQL($details_sql);
if ($details_res['num']>0) {
    $details['infosrc'] = 'wspmedia';
    $details['fullpath'] = cleanPath($details_res['set'][0]['filepath']);
    $details['fullfile'] = basename($details['fullpath']);
    $details['fullfold'] = trim(substr($details['fullpath'],0,-(strlen($details['fullfile']))));
    $details['filetype'] = substr($details['fullfile'],strrpos($details['fullfile'], '.'));
    $details['filename'] = substr($details['fullfile'],0,strrpos($details['fullfile'], '.'));
    $details['filesize'] = intval($details_res['set'][0]['filesize']);
    $details['filedate'] = intval($details_res['set'][0]['filedate']);
    $details['filedata'] = unserializeBroken($details_res['set'][0]['filedata']);
    $details['filekey']  = trim($fl);
}
else {
    $details['infosrc'] = 'filedata';
    // grep file information
    $file = unserializeBroken(base64_decode($fl));
    $details['fullpath'] = cleanPath($file[1]);
    $details['fullfile'] = basename($details['fullpath']);
    $details['fullfold'] = trim(substr($details['fullpath'],0,-(strlen($details['fullfile']))));
    $details['filetype'] = substr($details['fullfile'],strrpos($details['fullfile'], '.'));
    $details['filename'] = substr($details['fullfile'],0,strrpos($details['fullfile'], '.'));
    $details['filesize'] = 0;
    $details['filedate'] = 0;
    $details['filedata'] = false;
    $details['filekey']  = $fl = trim(base64_encode(serialize(array($details['fullfold'], $details['fullpath']))));
}

// more details
if (!(is_array($details['filedata'])) && ($details['filetype']=='.jpg' || $details['filetype']=='.png')) {
    $tmpdetails = @getimagesize(cleanPath(DOCUMENT_ROOT."/".$details['fullpath']));
    if (is_array($tmpdetails)) {
        if (intval($tmpdetails[0])>0) { $details['filedata']['width'] = intval($tmpdetails[0]); }
        if (intval($tmpdetails[0])>0) { $details['filedata']['height'] = intval($tmpdetails[1]); }
    }
}
if ($details['filesize']==0) {
    $details['filesize'] = @filesize(cleanPath(DOCUMENT_ROOT."/".$details['fullpath']));
}
// try to get thumbnail
$details['thumbnail'] = false;
foreach ($thumbtypes AS $tk => $tv) {
    $checkthumbpath = cleanPath(DOCUMENT_ROOT."/".str_replace($details['filetype'], ".".$tv, strl_replace($mediafolder[base64_decode($_REQUEST['ml'])], $mediafolder[base64_decode($_REQUEST['ml'])]."/thumbs/", cleanPath($details['fullpath']))));
    if (is_file($checkthumbpath)) {
        $details['thumbnail'] = cleanPath(strl_replace(DOCUMENT_ROOT, "/", $checkthumbpath));
    }
}
if (isset($_POST['action']) && $_POST['action']=='reloadthumb') {
    $details['thumbnail'] = false;
}
// try to get preview
$details['preview'] = false;
foreach ($previewtypes AS $pk => $pv) {
    $checkpreviewpath = cleanPath(DOCUMENT_ROOT."/".str_replace($details['filetype'], ".".$tv, strl_replace($mediafolder[base64_decode($_REQUEST['ml'])], $mediafolder[base64_decode($_REQUEST['ml'])]."/preview/", cleanPath($details['fullpath']))));
    if (is_file($checkpreviewpath)) {
        $details['preview'] = cleanPath(strl_replace(DOCUMENT_ROOT, "/", $checkpreviewpath));
    }
}

if ($details['thumbnail']===false && (strtolower($details['filetype'])=='.jpg' || strtolower($details['filetype'])=='.jpeg' || strtolower($details['filetype'])=='.gif' || strtolower($details['filetype'])=='.png')) {
    // try to generate thumbnail (again)
    $createthumbpath = cleanPath("/".str_replace($details['filetype'], ".png", strl_replace($mediafolder[base64_decode($_REQUEST['ml'])], $mediafolder[base64_decode($_REQUEST['ml'])]."/thumbs/", cleanPath($details['fullpath']))));
    // setting up thumbnail size
    $thumbsize = intval(getWSPProperties('thumbsize'));
    if ($thumbsize<150) { $thumbsize = 300; }
    // try resizing to tmp folder
    if (resizeGDimage(cleanPath(DOCUMENT_ROOT."/".$details['fullpath']), cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/".$details['filename'].".png"), 0, $thumbsize, $thumbsize, 1)) {
        $docopy = copyFile(cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR.$details['filename'].".png") , $createthumbpath);
        if ($docopy) {
            addWSPMsg('noticemsg', returnIntLang('mediadetails created new thumbnail'));
            $details['thumbnail'] = $createthumbpath;
        } else {
            addWSPMsg('errormsg', returnIntLang('mediadetails could not create new thumbnail'));
        }
    } else {
        addWSPMsg('errormsg', returnIntLang('mediadetails could not resize image to new thumbnail'));
    }
}
if ($details['preview']===false && $details['filetype']=='.pdf') {
	// try to generate pdf preview (again)
    if (class_exists("Imagick")) { 
        $imagick = new imagick();
        $imagick->setResolution (144, 144);
        $imagick->readImage($details['fullpath']);
        $imagick->flattenImages();
        $imgfile = $imagick->writeImages($tempfile, false);

        $scale[0] = 900;
        $scale[1] = 1200;
        
        resizeGDimage($tempfile, $workfile, 0, $scale[0], $scale[1], 1);



    }
}
if ($details['preview']===false && ($details['filetype']=='.jpg' || $details['filetype']=='.jpeg' || $details['filetype']=='.gif' || $details['filetype']=='.png')) {
	$details['preview'] = $details['fullpath'];
}

// check usage
$details['usage'] = false;
$details['usage_content'] = array();
$details['usage_global'] = array();
$details['usage_menu'] = array();
$details['usage_modtable'] = array();
$details['usage_style'] = array();
// check content usage
$cc_res = doSQL("SELECT c.`mid`, c.`cid` FROM `content` AS c, `menu` AS m  WHERE c.`trash` = 0 AND (c.`valuefields` LIKE '%".escapeSQL(trim($details['fullpath']))."%' OR c.`valuefields` LIKE '%".escapeSQL(trim($details['fullfile']))."%') AND m.`trash` = 0 AND c.`mid` = m.`mid`");
if ($cc_res['num']>0) {
	foreach ($cc_res['set'] AS $cck => $ccv) {
		$details['usage'] = true;
		$details['usage_content'][] = intval($ccv['cid']);
	}
}
// check global content usage
/*
// this checkes only USED global contents in contents -> but maybe they are in templates OR they are yet not used
$gc_res = doSQL("SELECT c.`mid` AS `mid`, c.`trash` AS `trashed`, g.`id` AS `id` FROM `content_global` AS g, `content` AS c WHERE g.`trash` = 0 AND g.`id` = c.`globalcontent_id` AND (g.`valuefields` LIKE '%".escapeSQL(trim($details['fullpath']))."%')");
*/
// this just checks existings global contents
$gc_res = doSQL("SELECT g.`id` AS `id` FROM `content_global` AS g WHERE g.`trash` = 0 AND (g.`valuefields` LIKE '%".escapeSQL(trim($details['fullpath']))."%')");
if ($gc_res['num']>0) {
    foreach ($gc_res['set'] AS $gck => $gcv) {
		$details['usage'] = true;
		$details['usage_global'][] = intval($gcv['id']);
	}
}
// check menuimage usage
$mc_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND (`imageon`='".escapeSQL(trim($details['fullpath']))."' OR `imageoff`='".escapeSQL(trim($details['fullpath']))."' OR `imageakt`='".escapeSQL(trim($details['fullpath']))."' OR `imageclick`='".escapeSQL(trim($details['fullpath'])). "')";
$mc_res = doSQL($mc_sql);
if ($mc_res['num']>0) {
	foreach ($mc_res['set'] AS $mck => $mcv) {
        $details['usage'] = true;
		$details['usage_menu'][] = intval($mcv['mid']);
    }
}
// check stylesheet usage
$sc_sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%".escapeSQL(trim($details['fullpath']))."%'";
$sc_res = doSQL($sc_sql);
if ($sc_res['num']>0) {
	foreach ($sc_res['set'] AS $sck => $scv) {
        $details['usage'] = true;
		$details['usage_style'][] = intval($scv['id']);
    }
}
// check modular usage
$moduleusage_sql = "SELECT `affectedcontent` FROM `modules` WHERE `affectedcontent` != '' && `affectedcontent` IS NOT NULL";
$moduleusage_res = doSQL($moduleusage_sql);
if ($moduleusage_res['num']>0) {
    foreach ($moduleusage_res['set'] AS $murk => $murv) {
        $grepdata = unserializeBroken($murv['affectedcontent']);
        foreach ($grepdata AS $table => $fieldnames) {
            $fileval_sql = array();
            foreach ($fieldnames AS $fieldname) {
                $fileval_sql[] = " `".$fieldname."` LIKE '%".escapeSQL(trim($details['fullpath']))."%' ";
            }
            $filemod_sql = "SELECT * FROM `".$table."` WHERE (".implode(" OR ", $fileval_sql).")";
            $filemod_num = getNumSQL($filemod_sql);
            if ($filemod_num>0) {
                $details['usage'] = true;
                $details['usage_modtable'][] = $table;
            }
        }
    }
}

if ($details['usage']===true) {
	doSQL("UPDATE `wspmedia` SET `embed` = 1, `lastchange` = ".time()." WHERE `filename` = '".escapeSQL(trim($details['fullfile']))."' AND `mediafolder` = '".escapeSQL(trim($details['fullfold']))."'");
}
else {
    doSQL("UPDATE `wspmedia` SET `embed` = 0, `lastchange` = ".time()." WHERE `filename` = '".escapeSQL(trim($details['fullfile']))."' AND `mediafolder` = '".escapeSQL(trim($details['fullfold']))."'");
}

// mediadesc pruefen - this will change nothing, if `mediadesc` isn't set, so we can do a soft conversion here
$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '".escapeSQL(trim($details['fullpath']))."'";
$desc_res = doSQL($desc_sql);
$details['mediadesc'] = '';
$details['mediakeys'] = '';
if ($desc_res['num']>0) {
	$details['mediadesc'] = trim($desc_res['set'][0]["filedesc"]);
	$details['mediakeys'] = trim($desc_res['set'][0]["filekeys"]);
    // remove the entry from mediadesc for soft conversion
    doSQL("DELETE FROM `mediadesc` WHERE `mediafile` = '".escapeSQL(trim($handle['fullpath']))."'");
}

// after all - we SAVE the data to database
$mediasave_sql = "UPDATE `wspmedia` SET 
    filedesc = '".escapeSQL($details['mediadesc'])."',
    filekeys = '".escapeSQL($details['mediakeys'])."',
    filedata = '".escapeSQL(serialize($details['filedata']))."'
    WHERE `filekey` = '".escapeSQL(trim($fl))."'";
doSQL($mediasave_sql);

require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('mediadetails headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('mediadetails info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php 
            
            $filedir = cleanPath("/".$details['fullfold']);
            $filelist = scanfiles($filedir);
            $filepos = array_search($details['fullfile'], $filelist);
            if (count($filelist)>1) {
                echo "<div class='row'>";
                echo "<div class='col-xs-6 col-sm-6 col-md-6 text-left'><p>";
                if ($filepos>0) {
                    echo "<a href='?fl=".base64_encode(serialize(array(base64_decode($_REQUEST['ml']), cleanPath($details['fullfold']."/".$filelist[($filepos-1)]))))."&ml=".trim($_REQUEST['ml'])."' class='btn btn-primary'>« ".$filelist[($filepos-1)]."</a>";
                } else {
                    echo "<a onclick=\"document.getElementById('back').submit();\" class='btn btn-primary'>".returnIntLang('str back', false)."</a>";   
                }
                echo "</p></div>";
                echo "<div class='col-xs-6 col-sm-6 col-md-6 text-right'><p>";
                if ($filepos<(count($filelist)-1)) {
                    echo "<a href='?fl=".base64_encode(serialize(array(base64_decode($_REQUEST['ml']), cleanPath($details['fullfold']."/".$filelist[($filepos+1)]))))."&ml=".trim($_REQUEST['ml'])."' class='btn btn-primary'>".$filelist[($filepos+1)]." »</a>";
                }
                echo "</p></div>";
                echo "</div>";
            }
            
            ?>
            <div class="row">
                <div class="col-md-12 col-lg-3 col-lg-push-9 preview-panel">
                    <!-- PANEL NO PADDING -->
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('mediadetails preview', true); ?> <?php if ($details['thumbnail']!==false): ?> &nbsp; <i class="fa fa-refresh" onclick="$('#reloadthumb').submit();"></i><?php endif; ?></h3>
                        </div>
                        <div class="panel-body">
                        <?php if ($details['thumbnail']!==false) { ?>
                                <div class="text-center">
                                    <a onclick="showImage('<?php echo $details['fullpath']; ?>')"><img src="<?php echo $details['thumbnail']; ?>" class="previewimg" /></a>
                                    <script>
                                        
                                    function showImage(imgPath) {
                                        $('.imagepreview').attr('src', imgPath);
                                        $('.imagepreview').css('background-image', imgPath);
                                        $('#imagemodal').modal('show');   
                                    };
                                        
                                    </script>
                                <div class="modal fade" id="imagemodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">              
                                            <div class="modal-body">
                                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                                <img src="" class="imagepreview" style="max-width: 100%;" >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <?php } ?>
                            <form id="uploadthumb" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                <input type="hidden" name="fl" value="<?php echo $fl; ?>" />
                                <input type="hidden" name="ml" value="<?php echo prepareTextField($_REQUEST['ml']); ?>" />
                                <input type="hidden" name="action" value="uploadthumb" />
                                <input name="uploadthumb" type="file" id="dropify-preview" data-allowed-file-extensions="jpg png" data-default-file="<?php /* if (is_file(DOCUMENT_ROOT."/media/screen/favicon.ico")): echo "/media/screen/favicon.ico"; endif; */ ?>">
                                <p>&nbsp;</p>
                                <p><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('mediadetails upload preview', false); ?>" /></p>
                            </form>
                        </div>
                        <form id="reloadthumb" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <input type="hidden" name="fl" value="<?php echo $fl; ?>" />
                            <input type="hidden" name="ml" value="<?php echo prepareTextField($_REQUEST['ml']); ?>" />
                            <input type="hidden" name="action" value="reloadthumb" />
                        </form>
                    </div>
                    <!-- END PANEL NO PADDING -->
                </div>
                <div class="col-md-12 col-lg-9 col-lg-pull-3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel" id="mediainfo">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('mediadetails fileinfo', true); ?></h3>
                                    <?php panelOpener(true, array(), false, 'mediainfo'); ?>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-3"><?php echo returnIntLang('mediadetails filepath', true); ?></div>
                                        <div class="col-sm-9"><p><?php echo "http://".str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['liveurl']."/".$details['fullpath'])); ?>&nbsp;&nbsp;&nbsp;<i class="fa fa-link fa-btn btn-primary"></i></p></div>
                                        <script>

                                            function copyToClipboard(element) {
                                                var $temp = $("<input>");
                                                $("body").append($temp);
                                                $temp.val($(element).text()).select();
                                                document.execCommand("copy");
                                                $temp.remove();
                                            }

                                        </script>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-3"><?php echo returnIntLang('mediadetails filesize', true); ?></div>
                                        <div class="col-sm-3"><p><?php echo showHumanSize($details['filesize'], 'MB', ' ', 2); ?></p></div>
                                        <div class="col-sm-3"><?php echo returnIntLang('mediadetails filedate', true); ?></div>
                                        <div class="col-sm-3"><p><?php 
                                            
                                            if ($details['filedate']>0) {
                                                echo date(returnIntLang('format date time', false), $details['filedate']);
                                            } else {
                                                echo returnIntLang('mediadetails filedate unknown', true);
                                            } ?></p></div>
                                    </div>
                                    <?php if (is_array($details['filedata'])) { ?>
                                    <div class="row">
                                        <div class="col-sm-3"><?php echo returnIntLang('mediadetails measures', true); ?></div>
                                        <div class="col-sm-9"><p><?php echo intval($details['filedata']['width']).' '.returnIntLang('mediadetails px').' × '.intval($details['filedata']['height']).' '.returnIntLang('mediadetails px'); ?></p></div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <form name="mediadescform" id="mediadescform" autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <input type="hidden" autocomplete="false" />
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('mediadetails filedata', true); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <p><?php echo returnIntLang('mediadetails filename', true); 
                                                    
                                                    if (urltext($details['filename'])!=trim($details['filename'])) { 
                                                        if ($details['usage']===true) {
                                                            echo " <span data-toggle='tooltip' data-placement='bottom' title='' data-original-title='".returnIntLang('mediadetails filename disabled help', false)."'><i class='fa fa-exclamation-triangle text-danger' aria-hidden='true'></i></span>";
                                                        }
                                                        else {
                                                            echo " <span data-toggle='tooltip' data-placement='bottom' title='' data-original-title='".returnIntLang('mediadetails filename help', false)."'><i class='fa fa-exclamation-triangle text-primary' aria-hidden='true'></i></span>";
                                                        }
                                                    } 
                                                    
                                                ?></p>
                                            </div>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="filename" value="<?php echo prepareTextField($details['filename']); ?>" />
                                                <div class="input-group form-group">
                                                    <input class="form-control <?php if ($details['usage']===true && urltext($details['filename'])!=trim($details['filename'])) { echo " bg-info "; } ?>" name="filename" id="filename" placeholder="<?php echo returnIntLang('mediadetails filename', true); ?>" onchange="checkMediaFileName();" <?php if ($details['usage']===true) { echo " disabled='disabled' "; } ?> type="text" value="<?php echo urltext($details['filename']); ?>" />
                                                    <span class="input-group-addon"><?php echo strtolower($details['filetype']); ?></span>
                                                </div>
                                                <input type="hidden" name="origname" id="origname" value="<?php echo prepareTextField($details['filename']); ?>" />
                                            </div>
                                            <script type="text/javascript">

                                                function checkMediaFileName() {
                                                    var newFileName = $('#filename').val();
                                                    var orgFileName = $('#origname').val();
                                                    if (newFileName!=orgFileName) {
                                                        $.post("xajax/ajax.checkmediafilename.php", {
                                                            'filefolder': '<?php echo $details['fullfold']; ?>',
                                                            'showfile': '<?php echo $details['fullpath']; ?>',
                                                            'filetype': '<?php echo $details['filetype']; ?>',
                                                            'newfilename': newFileName, 
                                                            'orgfilename': orgFileName
                                                        }).done (function(data) {
                                                            if (data!=1) {
                                                                $('#filename').prop('value', data);
                                                                }
                                                            })
                                                        }
                                                    }

                                            </script>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <p><?php echo returnIntLang('mediadetails title or file description', true); ?></p>
                                            </div>
                                            <div class="col-sm-9">
                                                <p><input type="text" class="form-control" name="mediadesc" placeholder="<?php echo prepareTextField(returnIntLang('mediadetails title or file description')); ?>" value="<?php echo prepareTextField($details['mediadesc']); ?>" /></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <p><?php echo returnIntLang('mediadetails file keywords', true); ?></p>
                                            </div>
                                            <div class="col-sm-9">
                                                <p><input type="text" class="form-control" name="mediakeys" placeholder="<?php echo prepareTextField(returnIntLang('mediadetails file keywords')); ?>" value="<?php echo prepareTextField($details['mediakeys']); ?>" /></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <input type="hidden" name="action" value="savefile">
                                                <input type="hidden" name="fl" class="form-control" value="<?php echo prepareTextField($fl); ?>" />
                                                <input type="hidden" name="ml" value="<?php echo prepareTextField($_REQUEST['ml']); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p><a href="#" onClick="document.getElementById('mediadescform').submit();" class="btn btn-primary"><?php echo returnIntLang('button save data', false); ?></a></p>
                            </form>
                        </div>
                        
                        <?php if ($details['usage']===true) { ?>
                            <div class="col-md-12">
                                <div class="panel" id="mediausage">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('mediadetails usage information', true); ?></h3>
                                        <?php panelOpener(true, array(), false, 'mediausage'); ?>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <?php 
                                                             
                                            if (count($details['usage_content'])>0) { 
                                                foreach ($details['usage_content'] AS $uck => $ucv) {
                                                    $mname_sql = "SELECT m.`description` FROM `menu` AS m, `content` AS c WHERE c.`cid` = ".intval($ucv)." AND c.`mid` = m.`mid`"; 
                                                    $mname_res = doSQL($mname_sql);
                                                    if ($mname_res['num']>0) {
                                                        echo "<div class='col-sm-3'><p>".returnIntLang('mediadetails usage content')." \"".setUTF8(trim($mname_res['set'][0]['description']))."\"</p></div>";
                                                    }
                                                }
                                            }
                                            
                                            if (count($details['usage_global'])>0) { 
                                                echo "<div class='col-sm-3'><p>".returnIntLang('mediadetails usage global')."</p></div>";
                                            }
                                                             
                                            if (count($details['usage_menu'])>0) { 
                                                foreach ($details['usage_menu'] AS $uck => $ucv) {
                                                    $mname_sql = "SELECT m.`description` FROM `menu` AS m WHERE m.`mid` = ".intval($ucv); 
                                                    $mname_res = doSQL($mname_sql);
                                                    if ($mname_res['num']>0) {
                                                        echo "<div class='col-sm-3'><p>".returnIntLang('mediadetails usage menu')." \"".setUTF8(trim($mname_res['set'][0]['description']))."\"</p></div>";
                                                    }
                                                }
                                            }
                                                             
                                            if (count($details['usage_modtable'])>0) { 
                                                foreach ($details['usage_modtable'] AS $uck => $ucv) {
                                                    $mname_sql = "SELECT `name` FROM `modules` WHERE `guid` = '".escapeSQL($ucv)."'"; 
                                                    $mname_res = doResultSQL($mname_sql);
                                                    if ($mname_res!==false) {
                                                        echo "<div class='col-sm-3'><p>".returnIntLang('mediadetails usage module', false)." \"".setUTF8(trim($mname_res))."\"</p></div>";
                                                    }
                                                }
                                            }
                                                             
                                            if (count($details['usage_style'])>0) { 
                                                foreach ($details['usage_style'] AS $uck => $ucv) {
                                                    $mname_sql = "SELECT `describ` FROM `stylesheets` WHERE `id` = ".intval($ucv); 
                                                    $mname_res = doResultSQL($mname_sql);
                                                    if ($mname_res!==false) {
                                                        echo "<div class='col-sm-3'><p>".returnIntLang('mediadetails usage style', false)." \"".setUTF8(trim($mname_res))."\"</p></div>";
                                                    }
                                                }
                                            }
                                            
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="row hide">
                <div class="col-md-12">
                    <!-- PANEL DEFAULT -->
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">Bild bearbeiten</h3>
                            <p class=""></p>
                            <div class="right">
                                <button type="button" class="btn-toggle-collapse"><i class="lnr lnr-chevron-up"></i></button>
                            </div>
                        </div>
                        <div class="panel-body">
                                    <div class="row">
                                    <div class="col-sm-3">
                                        <p>Skalieren</p>
                                    </div>
                                    <div class="col-sm-9">

                                    </div>
                                    </div>

                                    <div class="row">
                                    <div class="col-sm-3">
                                        <p>Format ändern</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p><select class="form-control">
                                        <option value="Profil">Profil</option>
                                        <option value="Banner">Banner</option>
                                        <option value="1377x866">1377x877</option>
                                        </select></p>
                                    </div>
                                    </div>

                                    <div class="row">
                                    <div class="col-sm-3">
                                        <p>Filter</p>
                                    </div>
                                    <div class="col-sm-9">

                                    </div>
                                </div>


                        </div>
                    </div>
                    <!-- END PANEL DEFAULT -->
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <p><a href="#" onclick="document.getElementById('back').submit();" class="btn btn-primary"><?php echo returnIntLang('str back', false); ?></a></p>
                    <form id="back" name="back" action="<?php 
                                                        
                        if (isset($_REQUEST['ml'])) {
                            echo $medialist[base64_decode($_REQUEST['ml'])];
                        }
                        else {
                            echo "imagemanagement.php";
                        }                            
                        
                        ?>" method="post">
                        <input type="hidden" name="medialoc" value="<?php 
                                                        
                        if (isset($_REQUEST['ml'])) {
                            echo $medialist[base64_decode($_REQUEST['ml'])];
                        }
                        else {
                            echo "imagemanagement.php";
                        }                            
                        
                        ?>" />
                    </form>
                    <form name="jumptodesign" id="jumptodesign" method="post" action="designedit.php">
                        <input name="op" type="hidden" value="edit" />
                        <input name="id" id="jumptodesignid" type="hidden" value="" />
                    </form>
                    <form name="jumptocontent" id="jumptocontent" method="post" action="contentedit.php">
                        <input name="op" type="hidden" value="edit" />
                        <input name="cid" id="jumptocontentid" type="hidden" value="" />
                    </form>
                    <form name="jumptoglobalcontent" id="jumptoglobalcontent" method="post" action="globalcontentedit.php">
                        <input name="gcid" id="jumptoglobalcontentid" type="hidden" value="" />
                    </form>
                    <form name="jumptomenu" id="jumptomenu" method="post" action="menuedit.php">
                        <input name="action" type="hidden" value="edit" />
                        <input name="mid" id="jumptomenuid" type="hidden" value="" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

$(document).ready(function() {
    
    $('.dropify').dropify();
    
    var drPreview = $('#dropify-preview').dropify({messages: { default: '<?php echo returnIntLang('mediadetails upload jpg or png file', false); ?>' }});
    // drPreview.on('dropify.beforeClear', function(event, element) {
    //     return confirm("<?php echo returnIntLang('mediadetails really delete file', false); ?> \"" + element.file.name + "\" ?");
    //     });
    // drPreview.on('dropify.afterClear', function(event, element) {
    //     alert('<?php echo returnIntLang('seo file will be deleted when saving', false); ?>');
    //     $('#removeset-favicon').val(1);
    //     });

    });

</script>
        
<?php include ("./data/include/footer.inc.php"); ?>