<?php
/**
 * Bearbeiten von Stylesheets
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-04
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['pagedesc'] = array('far fa-paint-brush', returnIntLang('menu design'), returnIntLang('menu design css'));
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    'dropify.css',
    'jquery.nestable.css',
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'dropify.js',
    'jquery/jquery.nestable.js',
    'jquery/jquery.autogrowtextarea.js'
    );
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
// define page specific funcs ----------------

// page actions
if (isset($_POST['op']) && $_POST['op']=="save" && trim($_POST['file'])!='') {
    $timestamp = time();
    if (intval($_POST['id'])>0) {
		// update exisiting file
		$sql = "UPDATE `stylesheets` SET 
			`file` = '".escapeSQL(trim($_POST['file']))."',
            `cfolder` = '".$timestamp."',
			`stylesheet` = '".escapeSQL($_POST['stylesheet'])."',
			`describ`='".escapeSQL(trim($_POST['describ']))."',
			`media` = '".escapeSQL($_POST['media'])."',
			`browser`='".escapeSQL($_POST['browser'])."',
			`lastchange` = ".$timestamp." 
			WHERE `id` = ".intval($_POST['id']);
	}
    else {
		$filenamecheck = getNumSQL("SELECT `file` FROM `stylesheets` WHERE `file` = '".escapeSQL(trim($_POST['file']))."'");
        // save new file
		$sql = "INSERT INTO `stylesheets` SET
			`file` = '".escapeSQL(trim($_POST['file'])).((intval($filenamecheck)>0)?'-'.intval($filenamecheck):'')."',
            `cfolder` = '".$timestamp."',
			`stylesheet`='".escapeSQL($_POST['stylesheet'])."',
			`describ`='".escapeSQL(trim($_POST['describ']))."',
			`media` = '".escapeSQL($_POST['media'])."',
			`browser`='".escapeSQL($_POST['browser'])."',
			`lastchange` = ".$timestamp;
	}
    $res = doSQL($sql);
	if ($res['aff']==1) { 
        addWSPMsg('noticemsg', returnIntLang('css saved css-file')); 
    }
    else { 
        addWSPMsg('errormsg', returnIntLang('css error saving css-file')); 
    }
}
else if (isset($_POST['op']) && $_POST['op']=="savefolder" && isset($_POST['id']) && intval($_POST['id'])>0) {
    // update css folder
    $stylesheet = null;
    if (array_key_exists('stylesheet', $_POST)) {
        $stylesheet = serialize($_POST['stylesheet']);
    }
    $sql = "UPDATE `stylesheets` SET 
        `stylesheet` = '".escapeSQL($stylesheet)."',
        `describ` = '".escapeSQL(trim($_POST['describfolder']))."',
        `lastchange` = ".time()."
        WHERE `id` = ".intval($_POST['id']);
    $res = doSQL($sql);
    if ($res['aff']==1) {
        addWSPMsg('noticemsg', returnIntLang('saved changes to cssfolder', false));	
    }
    else {
        addWSPMsg('errormsg', returnIntLang('error saving changes to cssfolder', false));
        $_POST['op'] = 'editfolder';
    }
}	
else if (isset($_FILES['uploadfile']) && trim($_FILES['uploadfile']['tmp_name'])!="") {
    if (intval($_FILES['uploadfile']['error'])==0 && intval($_FILES['uploadfile']['size'])>0) {
        if ($_FILES['uploadfile']['type']=='text/css') {
            $readsource = file_get_contents($_FILES['uploadfile']['tmp_name']);
            if ($readsource!==false) {
                $readname = trim($_FILES['uploadfile']['name']);
                $_REQUEST['op'] = 'edit';
                $_REQUEST['id'] = 0;
            }
            else {
                addWSPMsg('errormsg', returnIntLang('css file upload error reading file', false));
            }
        } 
        else {
            addWSPMsg('errormsg', returnIntLang('css file upload error false format', false));
        }
    }
    else {
        addWSPMsg('errormsg', returnIntLang('css file upload error uploading file', false));
    }
}
else if (isset($_FILES['uploadfolder']) && trim($_FILES['uploadfolder']['tmp_name'])!="") {
    if (intval($_FILES['uploadfolder']['error'])==0 && intval($_FILES['uploadfolder']['size'])>0) {
        if ($_FILES['uploadfolder']['type']=='application/zip' || $_FILES['uploadfolder']['type']=='application/x-gzip' || $_FILES['uploadfolder']['type']=='application/x-tar') {
            // get file information
            $scriptname = urltext(strr_replace(substr(basename($_FILES['uploadfolder']['name']), strrpos(basename($_FILES['uploadfolder']['name']), '.')), "", basename($_FILES['uploadfolder']['name'])));
            $filename = cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR.basename($_FILES['uploadfolder']['name']));
            $foldername = cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR.$scriptname);
            // remove older extracted archive, if not removed before by failure
            if (is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$foldername))) {
                deleteFolder(cleanPath($foldername), false);
            }
            // move file to tmp folder
            move_uploaded_file($_FILES['uploadfolder']['tmp_name'], cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$filename));
            try {
                // extract all files
                $phar = new PharData(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$filename));
                $phar->extractTo(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$foldername));
                $emptyextract = clearFolder($foldername, array('.css','.map','.png','.gif','.svg','.jpg','.otf','.woff','.woff2','.eot','.ttf','.md'));
                if ($emptyextract===true) {
                    deleteFolder($foldername, false);
                    addWSPMsg('errormsg', returnIntLang('folder upload was empty or had false contents', false));
                } else {
                    $copyfolder = copyFolder($foldername, cleanPath('/media/layout/'.$scriptname.'/'));
                    if ($copyfolder) {
                        deleteFolder($foldername, false);
                        deleteFile(cleanPath($filename));
                        // check for existing folder
                        $sql = "SELECT `id` FROM `stylesheets` WHERE `cfolder` = '".escapeSQL($scriptname)."'";
                        $res = doResultSQL($sql);
                        if ($res===false) {
                            // insert some data to database for a new entry
                            $sql = "INSERT INTO `stylesheets` SET `file` = '', `cfolder` = '".escapeSQL($scriptname)."', `describ` = '".escapeSQL($scriptname." ".returnIntLang('str folder', false))."', `lastchange` = ".time();
                            $res = doSQL($sql);
                            if ($res['inf']>0) {
                                addWSPMsg('resultmsg', returnIntLang('css folder upload done', false));
                            }
                        }
                        else {
                            $sql = "UPDATE `stylesheets` SET `lastchange` = ".time()." WHERE `cfolder` = '".escapeSQL($scriptname)."'";
                            $res = doSQL($sql);
                            if ($res['aff']>0) {
                                addWSPMsg('resultmsg', returnIntLang('css folder upload update done', false));
                            }
                        }
                    } else {
                        addWSPMsg('errormsg', returnIntLang('css folder upload error uploading file', false));
                    }
                }
            } 
            catch (Exception $e) {
                addWSPMsg('errormsg', returnIntLang('folder upload phar extracting failed1', false)." ".basename($filename)." ".returnIntLang('folder upload phar extracting failed2', false)." ".$foldername." ".returnIntLang('folder upload phar extracting failed3', false));
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('css folder upload error false format', false));
        }
    }
    else {
        addWSPMsg('errormsg', returnIntLang('css folder upload error uploading file', false));
    }
}
else if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="deletecss" && intval($_POST['id'])>0) {
    $sql = "SELECT `file` FROM `stylesheets` WHERE `id` = ".intval($_POST['id']);
    $file = doResultSQL($sql);
    $sql = "DELETE FROM `stylesheets` WHERE `id` = ".intval($_POST['id']);
	$res = doSQL($sql);
    if ($res['aff']==1) {
        if (trim($file)!='') {
            deleteFile('/media/layout/'.$file);
        }
		addWSPMsg('noticemsg', returnIntLang('css removed css-data and file'));
	}
}
else if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="deletefolder" && intval($_POST['id'])>0) {
    $sql = "SELECT `cfolder` FROM `stylesheets` WHERE `id` = ".intval($_POST['id']);
    $fld = doResultSQL($sql);
    $sql = "DELETE FROM `stylesheets` WHERE `id` = ".intval($_POST['id']);
	$res = doSQL($sql);
    if ($res['aff']==1) {
        if (trim($fld)!='') {
            deleteFolder('/media/layout/'.$fld, false);
        }
        addWSPMsg('noticemsg', returnIntLang('css removed css-data and folder'));
	}
}
else if (isset($_REQUEST['op']) && isset($_REQUEST['id']) && $_REQUEST['op']=='deletelost') {
    // remove 'lost' CSS-files from filesystem FINALLY
    deleteFile(base64_decode(trim($_POST['id'])));
}
else if (isset($_REQUEST['op']) && isset($_REQUEST['id']) && $_REQUEST['op']=='readlost') {
    if (isset($_REQUEST['sourcefile']) && trim($_REQUEST['sourcefile'])!='') {
        $sourcefile = true;
    }
    $_REQUEST['op'] = 'edit';
}

// site specific definitions
$mediaarray = array(
	"all" => returnIntLang('css mediatype allmedia', false),
	"screen" => returnIntLang('css mediatype screen', false),
	"print" => returnIntLang('css mediatype print', false),
);

$browserarray = array(
	"all" => returnIntLang('css browser allbrowser', false),
	"IE" => returnIntLang('css browser ieall', false),
	"lte IE 6" => returnIntLang('css browser ie6lower', false),
	"IE 6" => returnIntLang('css browser ie6', false),
	"gte IE 6" => returnIntLang('css browser ie6upper', false),
	"lte IE 7" => returnIntLang('css browser ie7lower', false),
	"IE 7" => returnIntLang('css browser ie7', false),
	"gte IE 7" => returnIntLang('css browser ie7upper', false),
	"lte IE 8" => returnIntLang('css browser ie8lower', false),
	"IE 8" => returnIntLang('css browser ie8', false),
	"gte IE 8" => returnIntLang('css browser ie8upper', false),
	"lte IE 9" => returnIntLang('css browser ie9lower', false),
	"IE 9" => returnIntLang('css browser ie9', false)
	);

// run folder for files
if (is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR))) {
    $scanfiles = scanfiles(cleanPath(DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR), SCANDIR_SORT_ASCENDING , false , array('css') );
} else {
    $created = createFolder(DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR);
    if ($created===true) {
        $scanfiles = scanfiles(cleanPath(DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR));
    } else {
        addWSPMsg('errormsg', 'could not detect layout folder');
        $scanfiles = array();
    }
}

// head of file - first regular output -------
require("./data/include/header.inc.php");
require("./data/include/navbar.inc.php");
require("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('css headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('css info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1);

            if (!(isset($_REQUEST['op'])) || (isset($_REQUEST['op']) && $_REQUEST['op']!='editfolder' && $_REQUEST['op']!='edit')):
	
            // prepare arrays holding data
            $foundcssfiles = array();
            $foundcsssize = array();
            $foundcssdate = array();
            $foundcsshash = array();
            foreach ($scanfiles AS $fk => $fv) {
                $foundcssfiles[] = $fv;
                $foundcsssize[$fv] = filesize(DOCUMENT_ROOT.DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR.$fv);
                $foundcssdate[$fv] = filemtime(DOCUMENT_ROOT.DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR.$fv);
                $foundcsshash[$fv] = base64_encode(trim(cleanPath(DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."layout".DIRECTORY_SEPARATOR.$fv)));
                clearstatcache();
            }
            
            // run database for saved files
            $css_sql = "SELECT `file` FROM `stylesheets` WHERE `cfolder` = `lastchange` ORDER BY `file`";
            $css_res = getResultSQL($css_sql);
            $syscssfiles = array();
            if (is_array($css_res) && count($css_res)>0) {
                foreach ($css_res AS $ck => $cv) {
                    $syscssfiles[] = trim($cv).".css";
                }
            }
            
            $lostcssfiles = array();
            $lostcssfiles = array_diff($foundcssfiles, $syscssfiles);
	
            if (count($lostcssfiles)>0) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('css found files'); ?> <span class="badge inline-badge"><?php echo count($lostcssfiles); ?></span></h3>
                            <?php panelOpener(true, array(), false); ?>
                        </div>
                        <div class="panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th><?php echo returnIntLang('str filename'); ?></th>
                                        <th><?php echo returnIntLang('str lastchange'); ?></th>
                                        <th><?php echo returnIntLang('str filesize'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lostcssfiles AS $lckey => $lcvalue): ?>
                                    <tr>
                                        <td><input type="checkbox" /></td>
                                        <td><?php echo $lcvalue; ?></td>
                                        <td><?php echo date(returnIntLang('format date', false), $foundcssdate[$lcvalue]); ?></td>
                                        <td><?php echo $foundcsssize[$lcvalue]; echo " ".returnIntLang('mediadetails space Byte', true); ?></td>
                                        <td class="text-right">
                                            <a onclick="editSource('<?php echo $foundcsshash[$lcvalue]; ?>','readlost',0);"><i class="fa fa-btn fa-download"></i></a>
                                            <a onclick="removeSource('<?php echo $foundcsshash[$lcvalue]; ?>','deletelost');"><i class="fa fa-btn fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <form name="deletelost" id="deletelost_form" method="post">
                <input type="hidden" name="op" value="deletelost" />
                <input type="hidden" name="id" id="deletelost_id" value="" />
            </form>
            <form name="readlost" id="readlost_form" method="post">
                <input type="hidden" name="op" value="readlost" />
                <input type="hidden" name="sourcefile" id="readlost_file" value="" />
                <input type="hidden" name="id" id="readlost_id" value="" />
            </form>
            <?php } ?>
            
            <div class="row hide">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading panel-working">
                            <h3 class="panel-title"><?php echo returnIntLang('css editclasses'); ?></h3>
                            <?php panelOpener(true, array(), false); ?>
                        </div>
                        <div class="panel-body" style="display: none;">
                            <form name="saveclasses" id="saveclasses" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                            <div class="row">
                                <div class="col-md-12">
                                    <p><?php echo returnIntLang('css contentclasses description'); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><?php echo returnIntLang('css classnames content'); ?></p>
                                    <div class="form-horizontal">
                                        <div class="form-group">
                                            <div class="col-md-12">
                                                <textarea name="contentclasses" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p><?php echo returnIntLang('css classnames contentholder'); ?></p>
                                    <div class="form-horizontal">
                                        <div class="form-group">
                                            <div class="col-md-12">
                                                <textarea name="contentholderclasses" class="form-control"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p><a onclick="document.getElementById('saveclasses').submit();" class="btn btn-primary"><?php echo returnIntLang('css saveclasses', false); ?></a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            
            // find css-FOLDERS
            $cssfolder_sql = "SELECT * FROM `stylesheets` WHERE `cfolder` != '' AND `file` = '' ORDER BY `describ`";
            $cssfolder_res = doSQL($cssfolder_sql);
            
            ?>
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-heading primary">
                            <h3 class="panel-title"><?php echo returnIntLang('css existingfolder'); ?> <span class="badge inline-badge"><?php echo $cssfolder_res['num']; ?></span></h3>
                        </div>
                        <div class="panel-body">
                            <?php if ($cssfolder_res['num']>0) { ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo returnIntLang('str folder'); ?></th>
                                        <th><?php echo returnIntLang('str description'); ?></th>
                                        <th><?php echo returnIntLang('str usage'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                    
                                foreach ($cssfolder_res['set'] AS $cfk => $cfv):
                                    echo "<tr>";
                                    echo "<td class='col-md-4'><a href=\"#\" onClick=\"document.getElementById('edit_cssfolder_".$cfv['id']."').submit();\">".$cfv['cfolder']."</a></td>";
                                    echo "<td class='col-md-4'>".$cfv['describ']."</td>";
                                    // usage output
                                    echo "<td class='col-md-3'>";
                                    
                                    $cssuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_styles` AS rtc, `templates` AS t WHERE rtc.`templates_id` = t.`id` AND rtc.`stylesheets_id` = ".intval($cfv['id']);
                                    $cssuse_res = doSQL($cssuse_sql);
                                    if ($cssuse_res['num']>0): 
                                        foreach($cssuse_res['set'] AS $cuk => $cuv):
                                            echo setUTF8($cuv['tname'])."<br />";
                                        endforeach;
                                    endif;
                                    
                                    $cssmenuuse_sql = "SELECT mj.`description` AS mdesc, mj.`mid` AS mid FROM `menu` AS mj WHERE mj.`addcss` LIKE '%\"".$cfv['id']."\"%'";
                                    $cssmenuuse_res = doSQL($cssmenuuse_sql);

                                    if ($cssmenuuse_res['num']>0):
                                        echo "Men&uuml;punkte:<br />"; 
                                        if ($cssmenuuse_num>5):
                                            $smushow = 5;
                                        else:
                                            $smushow = $cssmenuuse_num;
                                        endif;
                                        for($cures=0; $cures<$smushow; $cures++):
                                            echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".mysql_result($cssmenuuse_res, $cures, "mid")."\">".mysql_result($cssmenuuse_res, $cures, "mdesc")."</a><br />";
                                        endfor;
                                        if ($cssmenuuse_num>5):
                                            echo "<a style=\"cursor: pointer;\" id=\"showmore\" onclick=\"document.getElementById('hidemore').style.display = 'block'; document.getElementById('showmore').style.display = 'none';\" >".($cssmenuuse_num-5)." weitere ..</a>";
                                            echo "<span id=\"hidemore\" style=\"display: none;\">";
                                            for($cures=5; $cures<$cssmenuuse_num; $cures++):
                                                echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".mysql_result($cssmenuuse_res, $cures, "mid")."\">".mysql_result($cssmenuuse_res, $cures, "mdesc")."</a><br />";
                                            endfor;
                                            echo "</span>";
                                        endif;
                                    endif;
                                    
                                    echo "</td>";
                                    // action fields
                                    echo "<td class='col-md-1 text-right'>";
                                    // edit call form
                                    echo "<form name=\"edit_cssfolder_".$cfv['id']."\" id=\"edit_cssfolder_".$cfv['id']."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
                                    echo "<input name='op' type='hidden' value='editfolder' />";
                                    echo "<input name='id' type='hidden' value='".$cfv['id']."' />";
                                    echo "</form>\n";
                                    echo "<form name='edit_cssfolder_".$cfv['id']."' id='edit_removefolder_".$cfv['id']."' action='".$_SERVER['PHP_SELF']."' method='post'>";
                                    echo "<input name='op' type='hidden' value='deletefolder' />";
                                    echo "<input name='id' type='hidden' value='".$cfv['id']."' />";
                                    echo "</form>\n";
                                    
                                    echo "<a href='#' onClick=\"document.getElementById('edit_cssfolder_".$cfv['id']."').submit();\"><i class='fa fa-pencil-alt fa-btn '></i></a> ";
                                    
                                    echo "<a href='#' onClick=\"document.getElementById('edit_removefolder_".$cfv['id']."').submit();\"><i class='fa fa-trash fa-btn '></i></a> ";
                                    
                                    echo "</td>";
                                    echo "</tr>";
                                endforeach; ?>
                                </tbody>
                            </table>
                            <?php } else {
                                echo '<p>'.returnIntLang('css no folders found').'<p>';
                            } ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('css upload folder'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <form name="uploadfolder_css" id="uploadfolder_css" method="post" enctype="multipart/form-data">
                            <p><input name="uploadfolder" type="file" id="dropify-uploadfolder" data-height="100" data-allowed-file-extensions="tgz tar zip" /></p>
                            <p class="submitter" style="display: none;"><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('str do upload', false); ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            
            // find css-FILES
            $cssfiles_sql = "SELECT `id`, `file`, `cfolder`, `describ`, `media`, `browser`, char_length(`stylesheet`) AS `filesize` FROM `stylesheets` WHERE `cfolder` = `lastchange` AND `file` != '' ORDER BY `describ`";
            $cssfiles_res = doSQL($cssfiles_sql);
            
            ?>
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-heading primary">
                            <h3 class="panel-title"><?php echo returnIntLang('css existingfiles'); ?> <span class="badge inline-badge"><?php echo $cssfiles_res['num']; ?></span></h3>
                        </div>
                        <div class="panel-body">
                            <?php if ($cssfiles_res['num']>0) { ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo returnIntLang('str description'); ?> [ <?php echo returnIntLang('str filename'); ?> ]</th>
                                        <th><?php echo returnIntLang('css mediatype'); ?></th>
                                        <th><?php echo returnIntLang('str usage'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cssfiles_res['set'] AS $cfk => $cfv): ?>
                                    <tr>
                                        <td class='col-md-4'><?php 
                                        echo "<a style='cursor: pointer;' onClick=\"document.getElementById('edit_design_".$cfk."').submit();\">".$cfv['describ']." [ ".$cfv['file'].".css ]</a>"; ?></td>
                                        <td class='col-md-4'><?php echo $mediaarray[$cfv['media']]." | ".$browserarray[$cfv['browser']]; ?></td>
                                        <td class='col-md-3'><?php
                                        $cssuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_styles` AS rtc, `templates` AS t WHERE rtc.`templates_id` = t.`id` AND rtc.`stylesheets_id` = ".intval($cfv['id']);
                                        $cssuse_res = doSQL($cssuse_sql);
                                        if ($cssuse_res['num']>0):
                                            foreach ($cssuse_res['set'] AS $cursk => $cursv) {
                                                echo "<a href='./templates.php?op=edit&id=".intval($cursv['tid'])."'>".setUTF8(trim($cursv['tname']))."</a><br />";
                                            }
                                        else:
                                            echo returnIntLang('str no usage', false);
                                        endif;
                                        ?></td>
                                        <td class="col-md-1 text-right text-nowrap"><?php
                                        echo "<a onClick=\"document.getElementById('edit_design_".$cfk."').submit();\"><i class='fas fa-btn fa-pencil-alt'></i></a> ";
                                        
                                        if (array_key_exists(($cfv['file'].'.css'),$foundcsssize) && $foundcsssize[($cfv['file'].'.css')]!=$cfv['filesize']):
                                            // if db-size differs from filesystem-size allow rollback from filesystem
                                            echo "<a onclick=\"editSource('".$foundcsshash[($cfv['file'].'.css')]."','readlost',".intval($cfv['id']).");\"><i class='fas fa-btn fa-sync-alt'></i></a> ";
                                        endif;
                                        if ($cssuse_res['num']==0):
                                            echo "<a onclick=\"return confirmDelete('".$cfv['describ']."', ".intval($cfv['id']).");\" ><i class='fa fa-btn fa-trash'></i></a> ";
                                        else:
                                            echo "<i class='fa fa-btn fa-trash fa-disabled'></i> ";
                                        endif;
                                        echo "\t<form name='edit_design_".$cfk."' id='edit_design_".$cfk."' method='post'>";
                                        echo "<input name='op' id='' type='hidden' value='edit' />";
                                        echo "<input name='id' id='' type='hidden' value='".intval($cfv['id'])."' />";
                                        echo "</form>\n";
                                        ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                </tbody>    
                            </table>
                            <?php } else { echo '<p>'.returnIntLang('css no files found').'<p>'; } ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('css upload files'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <form name="uploadfile_css" id="uploadfile_css" method="post" enctype="multipart/form-data">
                            <p><input name="uploadfile" type="file" id="dropify-uploadfile" data-height="100" data-allowed-file-extensions="css" /></p>
                            <p class="submitter" style="display: none;"><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('str do upload', false); ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?op=edit&id=0" class="btn btn-primary"><?php echo returnIntLang('css createnewcss', false); ?></a></p>
            
            <form name="deletecss" id="deletecss_form" method="post">
                <input type="hidden" name="op" value="deletecss" />
                <input type="hidden" name="id" id="deletecss_id" value="" />
            </form>
            
            <?php endif; // end of non edit ?>
    
            <?php if(isset($_REQUEST['op']) && isset($_REQUEST['id']) && $_REQUEST['op']=='editfolder' && intval($_REQUEST['id'])>0): 
                
                // editing css folder contents
            
                $cssfolder_sql = "SELECT * FROM `stylesheets` WHERE `id` = ".intval($_REQUEST['id']);
                $cssfolder_res = doSQL($cssfolder_sql);

                if ($cssfolder_res['num']!=0):
                    $describ = $cssfolder_res['set'][0]['describ'];
                    $cfolder = $cssfolder_res['set'][0]['cfolder'];
                    $usedones = unserializeBroken($cssfolder_res['set'][0]['stylesheet']);
                endif;
                
                ?>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscriptfolder" name="formscriptfolder">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading primary">
                                <h3 class="panel-title"><?php echo returnIntLang('css editfolder'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <?php echo returnIntLang('str description'); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-horizontal">
                                            <div class="form-group">
                                                <div class="col-md-12">
                                                    <input type="text" class="form-control" name="describfolder" id="describfolder" value="<?php echo $describ; ?>" placeholder="<?php echo returnIntLang('str description', false); ?>" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <?php echo returnIntLang('str folder'); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-horizontal">
                                            <div class="form-group">
                                                <div class="col-md-12">
                                                    <input type="text" class="form-control" value="<?php echo $cfolder; ?>" placeholder="<?php echo returnIntLang('str folder', false); ?>" disabled="disabled" readonly="readonly" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <?php echo returnIntLang('css folderfiles'); ?>
                                    </div>
                                    <div class="col-md-10">
                                        <?php
                                        
                                        $path = "/media/layout/".$cfolder."/";
                                        $subs = dirList($path, '/media/layout/', true, false);
                                        if (is_array($subs) && count($subs)>0) {
                                            array_unshift($subs, $path);
                                        } else {
                                            $subs = array($path);
                                        }
                                        $usefiles = array();
                                        foreach ($subs AS $sdk => $sdv) {
                                            foreach (scanfiles(cleanPath($sdv)) AS $sfk => $sfv) {
                                                $usefiles[] = cleanPath(str_replace($path, '/', $sdv."/".$sfv));
                                            }
                                        }
                                        if (count($usefiles)>0):
                                            if (is_array($usedones)):
                                                $temporderusefiles = array();
                                                foreach ($usefiles AS $key => $value):
                                                    if (in_array($value, $usedones)):
                                                        $upkey = array_keys($usedones, $value);
                                                        $temporderusefiles[($upkey[0])] = $value;
                                                    else:
                                                        $temporderusefiles[(count($usefiles)+$key)] = $value;
                                                    endif;
                                                    $temporderusefiles[] = $value;
                                                endforeach;
                                                unset($usefiles);
                                                $usefiles = array_unique($temporderusefiles);
                                                ksort($usefiles);
                                            endif;
                                            echo "<div class='dd' id='sortcssfolder'><ol class='dd-list' style='margin: 0px; margin-bottom: 10px; padding: 0px; list-style-type: none;'>";
                                            foreach ($usefiles AS $key => $value):
                                                echo "<li data-id='sortcssfolder_".$key."' class='dd-item li-btn btn-sm ".((is_array($usedones) && in_array($value, $usedones))?'btn-info':'btn-default')."'>";
                                                echo "<input type='checkbox' name='stylesheet[]' id='usefiles_".$key."' value='".$value."' ";
                                                if (is_array($usedones) && in_array($value, $usedones)):
                                                    echo " checked='checked' ";
                                                endif;
                                                echo " /> &nbsp; <span style='cursor: move;' class='dd-move'>".$value."</span></li>";
                                            endforeach;
                                            echo "</ol></div>";

                                        else:
                                            echo "<p>".returnIntLang('css folder doesnt exist')."</p>";
                                        endif;

                                        ?>
                                        
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p><?php echo returnIntLang('css move activated files to correct order by drag and drop'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script type="text/javascript" language="javascript" charset="utf-8">
		
                $(function() {
                    $("#sortcssfolder").nestable({
                        handleClass: 'dd-move',
                        maxDepth: 1,
                    });
                });

                </script>
                <input type="hidden" name="id" value="<?php echo intval($_REQUEST['id']); ?>" />
                <input type="hidden" name="op" value="savefolder" />
                <p><a onclick="document.getElementById('formscriptfolder').submit();" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a> <a href="?" class="btn btn-warning"><?php echo returnIntLang('str cancel'); ?></a></p>
                </form>
            <?php endif; // end of edit folder ?>
            
            <?php if(isset($_REQUEST['op']) && isset($_REQUEST['id']) && $_REQUEST['op']=='edit'): 
                
                // editing css file contents    
                // setup empty values if new or none found by id
                $id = intval($_REQUEST['id']);
                $describ = '';
                $file = '';
                $stylesheet = '';
                $media = 'all';
                $browser = 'all';
                if (isset($sourcefile) && $sourcefile===true) {
                    $readsource = DOCUMENT_ROOT.base64_decode(trim($_REQUEST['sourcefile']));
                    if (is_file($readsource)) {
                        $sourcename = substr($readsource, (strrpos($readsource, "/")+1));
                        $describ = $file = substr($sourcename, 0, -4);
                        $stylesheet = file_get_contents($readsource);
                    }
                }
                else if (isset($readsource) && $readsource!==false) {
                    $describ = $file = substr(basename($readname), 0, -4);
                    $stylesheet = $readsource;
                }
                elseif (intval($_REQUEST['id'])>0) {
                    $design_sql = "SELECT `id`, `file`, `describ`, `stylesheet`, `media`, `browser` FROM `stylesheets` WHERE `id` = ".intval($_REQUEST['id']);
		            $design_res = doSQL($design_sql);
                    if ($design_res['num']>0) {
                        $id = intval($_REQUEST['id']);
                        $describ = $design_res['set'][0]['describ'];
                        $file = $design_res['set'][0]['file'];
                        $stylesheet = stripslashes($design_res['set'][0]['stylesheet']);
                        $media = $design_res['set'][0]['media'];
                        $browser = $design_res['set'][0]['browser'];
                    }
                }
                
                ?>
                <form method="post" enctype="multipart/form-data" id="formdesign">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading primary">
                                <h3 class="panel-title"><?php if($id>0): echo returnIntLang('css editfile'); else: echo returnIntLang('css createfile'); endif; ?></h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-2"><?php echo returnIntLang('str description'); ?></div>
                                    <div class="col-md-4">
                                        <div class="form-horizontal">
                                            <div class="form-group">
                                                <div class="col-md-12">
                                                    <input type="text" class="form-control" required="required" name="describ" id="describ" value="<?php echo $describ; ?>" placeholder="<?php echo returnIntLang('str description', false); ?>" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2"><?php echo returnIntLang('str filename'); ?></div>
                                    <div class="col-md-4">
                                        <div class="form-horizontal">
                                            <div class="input-group">
                                                <input class="form-control" type="text" required="required" name="file" id="file" value="<?php echo $file; ?>" placeholder="<?php echo returnIntLang('str filename', false); ?>">
                                                <span class="input-group-addon">.css</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2"><?php echo returnIntLang('str description'); ?></div>
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <textarea name="stylesheet" id="stylesheet_area" rows="10" class="form-control allowTabChar" wrap="off"><?php
                                            $stylerows = explode("\n",$stylesheet);
                                            for ($r=0;$r<count($stylerows);$r++):
                                                echo $stylerows[$r]."\n";
                                            endfor; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2"><?php echo returnIntLang('css mediatype label'); ?></div>
                                    <div class="col-md-4">
                                        <div class="form-horizontal">
                                            <select name="media"><?php

                                            foreach ($mediaarray AS $key => $value):
                                                echo "<option value=\"".$key."\"";
                                                if ($media==$key):
                                                    echo " selected=\"selected\"";
                                                endif;
                                                echo ">".$value."</option>";
                                            endforeach;

                                            ?></select>
                                        </div>
                                    </div>
                                    <div class="col-md-2"><?php echo returnIntLang('css browser'); ?></div>
                                    <div class="col-md-4">
                                        <div class="form-horizontal">
                                            <select name="browser"><?php

                                            foreach ($browserarray AS $key => $value):
                                                echo "<option value=\"".$key."\"";
                                                if ($browser==$key):
                                                    echo " selected=\"selected\"";
                                                endif;
                                                echo ">".$value."</option>";
                                            endforeach;

                                            ?></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="op" value="save" /><input type="hidden" name="id" value="<?php echo $id; ?>" />
                <p><button onclick="document.getElementById('formdesign').submit();" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></button> <a href="?" class="btn btn-warning"><?php echo returnIntLang('str cancel'); ?></a></p>
                </form>
            <?php endif; // end of edit file ?>
        </div>
    </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script language="JavaScript" type="text/javascript">
<!--

function confirmDelete(designname, cssid) {
    if (confirm('<?php echo returnIntLang('css removemessage1', false); ?>' + designname + '<?php echo returnIntLang('css removemessage2', false); ?>')) {
        $('#deletecss_id').val(cssid);
        $('#deletecss_form').submit();
        }
    }

function editSource(file, sourcelist, fileID) {
    if (fileID!=0) { 
        $('#' + sourcelist + '_id').val(fileID);
    }
    $('#' + sourcelist + '_file').val(file);
    $('#' + sourcelist + '_form').submit();
}
    
function removeSource(file, sourcelist) {
    $('#' + sourcelist + '_id').val(file);
    $('#' + sourcelist + '_form').submit();
}

$(function() {
    
    $(".allowTabChar").allowTabChar();
    $('.autogrow').autoGrow();
    $('.dropify').dropify();
    
    var drFolder = $('#dropify-uploadfolder').dropify({messages: { default: 'Upload TGZ or ZIP' }});
    
    drFolder.on('dropify.afterReady', function(event, element) {
        $('#uploadfolder_css .submitter').show();
        });
    drFolder.on('dropify.afterClear', function(event, element) {
        $('#uploadfolder_css .submitter').hide();
        });
    
    var drFile = $('#dropify-uploadfile').dropify({messages: { default: 'Upload CSS' }});
    drFile.on('dropify.afterReady', function(event, element) {
        $('#uploadfile_css .submitter').show();
        });
    drFile.on('dropify.afterClear', function(event, element) {
        $('#uploadfile_css .submitter').hide();
        });
    })
    
// -->
</script>

<?php require ("./data/include/footer.inc.php"); ?>