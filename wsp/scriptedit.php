<?php
/**
 * editing JavaScript files and folders
 * @author stefan@covi.de
 * @since 3.4
 * @version 7.0
 * @lastchange 2021-09-15
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position ------------
$_SESSION['wspvars']['lockstat'] = 'javascript';
$_SESSION['wspvars']['pagedesc'] = array('far fa-paint-brush', returnIntLang('menu design'), returnIntLang('menu design js'));
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
if (isset($_POST['op']) &&  trim($_POST['op'])=="save" && trim($_POST['file'])!='') {
    $timestamp = time();
    if (intval($_POST['id'])>0) {
		// update exisiting file
		$sql = "UPDATE `javascript` SET 
			`file` = '".escapeSQL(trim($_POST['file']))."',
            `cfolder` = '".$timestamp."',
			`scriptcode` = '".escapeSQL($_POST['scriptcode'])."',
			`describ` = '".escapeSQL($_POST['describ'])."',
			`lastchange` = '".$timestamp."'
			WHERE `id` = ".intval($_POST['id']);
	}
    else {
        $filenamecheck = getNumSQL("SELECT `file` FROM `stylesheets` WHERE `file` = '".escapeSQL(trim($_POST['file']))."'");
		// neues JavaScript
		$sql = "INSERT INTO `javascript` SET 
			`file`='".escapeSQL(trim($_POST['file'])).((intval($filenamecheck)>0)?'-'.intval($filenamecheck):'')."',
            `cfolder` = '".$timestamp."',
			`scriptcode`='".escapeSQL($_POST['scriptcode'])."',
			`describ`='".escapeSQL($_POST['describ'])."',
			`lastchange` = ".$timestamp;
	}
	$res = doSQL($sql);
	if ($res['aff']==1) { 
        addWSPMsg('noticemsg', returnIntLang('js saved js-file')); 
    }
    else { 
        addWSPMsg('errormsg', returnIntLang('js error saving js-file'));
    }
}
else if (isset($_POST['op']) &&  trim($_POST['op'])=="savefolder" && isset($_POST['id']) && intval($_POST['id'])>0) {
    // update javascript folder
    $scriptcode = null;
    if (array_key_exists('scriptcode', $_POST)) { 
        $scriptcode = serialize($_POST['scriptcode']);
    }
    $sql = "UPDATE `javascript` SET 
        `scriptcode` = '".escapeSQL($scriptcode)."',
        `describ` = '".escapeSQL(trim($_POST['describfolder']))."',
        `lastchange` = ".time()."
        WHERE `id` = ".intval($_POST['id']);
    $res = doSQL($sql);
	if ($res['aff']==1) {
        addWSPMsg('noticemsg', returnIntLang('saved changes to jsfolder', false));	
	} else {
		addWSPMsg('errormsg', returnIntLang('error saving changes to jsfolder', false));
        $_POST['op'] = 'editfolder';
	}
}
else if (isset($_FILES['uploadfile']) && trim($_FILES['uploadfile']['tmp_name'])!="") {
    if (intval($_FILES['uploadfile']['error'])==0 && intval($_FILES['uploadfile']['size'])>0) {
        if ($_FILES['uploadfile']['type']=='application/x-javascript') {
            $readsource = file_get_contents($_FILES['uploadfile']['tmp_name']);
            if ($readsource!==false) {
                $readname = trim($_FILES['uploadfile']['name']);
                $_REQUEST['op'] = 'edit';
                $_REQUEST['id'] = 0;
            }
            else {
                addWSPMsg('errormsg', returnIntLang('js file upload error reading file', false));
            }
        } 
        else {
            addWSPMsg('errormsg', returnIntLang('js file upload error false format', false));
        }
    }
    else {
        addWSPMsg('errormsg', returnIntLang('js file upload error uploading file', false));
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
                $emptyextract = clearFolder($foldername, array('.js','.css','.map','.png','.gif','.svg','.jpg','.rb','.less','.scss','.eot','.ttf','.woff','.woff2','.md'));
                if ($emptyextract===true) {
                    deleteFolder($foldername, false);
                    addWSPMsg('errormsg', returnIntLang('folder upload was empty or had false contents', false));
                } else {
                    $copyfolder = copyFolder($foldername, cleanPath('/data/script/'.$scriptname.'/'));
                    if ($copyfolder) {
                        deleteFolder($foldername, false);
                        deleteFile(cleanPath($filename));
                        // check for existing folder
                        $sql = "SELECT `id` FROM `javascript` WHERE `cfolder` = '".escapeSQL($scriptname)."'";
                        $res = doResultSQL($sql);
                        if ($res===false) {
                            // insert some data to database for a new entry
                            $sql = "INSERT INTO `javascript` SET `file` = '', `cfolder` = '".escapeSQL($scriptname)."', `describ` = '".escapeSQL($scriptname." ".returnIntLang('str folder', false))."', `lastchange` = ".time();
                            $res = doSQL($sql);
                            if ($res['inf']>0) {
                                addWSPMsg('resultmsg', returnIntLang('js folder upload done', false));
                            }
                        }
                        else {
                            $sql = "UPDATE `javascript` SET `lastchange` = ".time()." WHERE `cfolder` = '".escapeSQL($scriptname)."'";
                            $res = doSQL($sql);
                            if ($res['aff']>0) {
                                addWSPMsg('resultmsg', returnIntLang('js folder upload update done', false));
                            }
                        }
                    } else {
                        addWSPMsg('errormsg', returnIntLang('js folder upload error uploading file', false));
                    }
                }
            } 
            catch (Exception $e) {
                addWSPMsg('errormsg', returnIntLang('folder upload phar extracting failed1', false)." ".basename($filename)." ".returnIntLang('folder upload phar extracting failed2', false)." ".$foldername." ".returnIntLang('folder upload phar extracting failed3', false));
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('js folder upload error false format', false));
        }
    }
    else {
        addWSPMsg('errormsg', returnIntLang('js folder upload error uploading file', false));
    }
}
else if (isset($_POST['op']) &&  trim($_POST['op'])=="deletejs" && intval($_POST['id'])>0) {
	$sql = "SELECT `file` FROM `javascript` WHERE `id` = ".intval($_POST['id']);
    $file = doResultSQL($sql);
    $sql = "DELETE FROM `javascript` WHERE `id` = ".intval($_POST['id']);
	$res = doSQL($sql);
    if ($res['aff']==1) {
        if (trim($file)!='') {
            deleteFile('/data/script/'.$file);
        }
		addWSPMsg('noticemsg', returnIntLang('js removed js-data and file'));
	}
}
else if (isset($_REQUEST['op']) &&  trim($_REQUEST['op'])=="deletefolder" && intval($_POST['id'])>0) {
    $sql = "SELECT `cfolder` FROM `javascript` WHERE `id` = ".intval($_POST['id']);
    $fld = doResultSQL($sql);
    $sql = "DELETE FROM `javascript` WHERE `id` = ".intval($_POST['id']);
	$res = doSQL($sql);
    if ($res['aff']==1) {
        if (trim($fld)!='') {
            deleteFolder('/data/script/'.$fld, false);
        }
        addWSPMsg('noticemsg', returnIntLang('js removed js-data and folder'));
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

// run folder for files
if (is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR))) {
    $scanfiles = scanfiles(cleanPath(DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR), SCANDIR_SORT_ASCENDING , false , array('js') ); 
} else {
    $created = createFolder(DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR);
    if ($created===true) {
        $scanfiles = scanfiles(cleanPath(DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR));
    } else {
        addWSPMsg('errormsg', 'could not detect script folder');
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
                <h1 class="page-title"><?php echo returnIntLang('js headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('js info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
            
            if (!(isset($_REQUEST['op'])) || (isset($_REQUEST['op']) && $_REQUEST['op']!='editfolder' && $_REQUEST['op']!='edit')):
            
            // run folder for files ...
            $foundjsfiles = array();
            foreach ($scanfiles AS $fk => $fv) {
                $foundjsfiles[] = $fv;
                $foundjssize[$fv] = filesize(DOCUMENT_ROOT.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR.$fv);
                $foundjsdate[$fv] = filemtime(DOCUMENT_ROOT.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR.$fv);
                $foundjshash[$fv] = base64_encode(trim(cleanPath(DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR.$fv)));
                clearstatcache();
            }
            
            // run database for saved files
            $js_sql = "SELECT `file` FROM `javascript` WHERE `cfolder` = `lastchange` ORDER BY `file`";
            $js_res = getResultSQL($js_sql);

            $sysjsfiles = array();
            if (is_array($js_res) && count($js_res)>0) {
                foreach ($js_res AS $jk => $jv) {
                    $sysjsfiles[] = trim($jv).".js";
                }
            }
	
            $lostjsfiles = array();
            $lostjsfiles = array_diff($foundjsfiles, $sysjsfiles);
            
            if (count($lostjsfiles)>0) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('js found files'); ?> <span class="badge inline-badge"><?php echo count($lostjsfiles); ?></span></h3>
                            <?php panelOpener(true, array(), false); ?>
                        </div>
                        <div class="panel-body" style="display: none;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#all</th>
                                        <th><?php echo returnIntLang('str filename'); ?></th>
                                        <th><?php echo returnIntLang('str lastchange'); ?></th>
                                        <th><?php echo returnIntLang('str filesize'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lostjsfiles AS $ljkey => $ljvalue): ?>
                                    <tr>
                                        <td>#</td>
                                        <td><?php echo $ljvalue; ?></td>
                                        <td><?php echo date(returnIntLang('format date', false), $foundjsdate[$ljvalue]); ?></td>
                                        <td><?php echo $foundjssize[$ljvalue]; echo " ".returnIntLang('mediadetails space Byte', true); ?></td>
                                        <td class="text-right">
                                            <a onclick="editSource('<?php echo $foundjshash[$ljvalue]; ?>','readlost',0);"><i class="fa fa-btn fa-download"></i></a>
                                            <a onclick="removeSource('<?php echo $foundjshash[$ljvalue]; ?>','deletelost');"><i class="fa fa-btn fa-trash"></i></a>
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
            <?php } 
            
            // find js-FOLDERS
            $jsfolder_sql = "SELECT * FROM `javascript` WHERE `cfolder` != '' AND `file` = '' ORDER BY `describ`";
            $jsfolder_res = doSQL($jsfolder_sql);
            
            ?>
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-heading primary">
                            <h3 class="panel-title"><?php echo returnIntLang('js existingfolder'); ?> <span class="badge inline-badge"><?php echo $jsfolder_res['num']; ?></span></h3>
                        </div>
                        <div class="panel-body">
                            <?php if ($jsfolder_res['num']>0) { ?>
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
                                    
                                foreach ($jsfolder_res['set'] AS $jfk => $jfv):
                                    echo "<tr>";
                                    echo "<td class='col-md-4'><a href=\"#\" onClick=\"document.getElementById('edit_jsfolder_".$jfv['id']."').submit();\">".$jfv['cfolder']."</a></td>";
                                    echo "<td class='col-md-4'>".$jfv['describ']."</td>";
                                    // usage output
                                    echo "<td class='col-md-3'>";
                                    
                                    $jsuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_styles` AS rtc, `templates` AS t WHERE rtc.`templates_id` = t.`id` AND rtc.`stylesheets_id` = ".intval($jfv['id']);
                                    $jsuse_res = doSQL($jsuse_sql);
                                    if ($jsuse_res['num']>0): 
                                        foreach($jsuse_res['set'] AS $cuk => $cuv):
                                            echo setUTF8($cuv['tname'])."<br />";
                                        endforeach;
                                    endif;
                                    
                                    $jsmenuuse_sql = "SELECT mj.`description` AS mdesc, mj.`mid` AS mid FROM `menu` AS mj WHERE mj.`addscript` LIKE '%\"".$jfv['id']."\"%'";
                                    $jsmenuuse_res = doSQL($jsmenuuse_sql);

                                    if ($jsmenuuse_res['num']>0):
                                        echo "Men&uuml;punkte:<br />"; 
                                        if ($jsmenuuse_num>5):
                                            $smushow = 5;
                                        else:
                                            $smushow = $jsmenuuse_num;
                                        endif;
                                        for($cures=0; $cures<$smushow; $cures++):
                                            echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".mysql_result($jsmenuuse_res, $cures, "mid")."\">".mysql_result($jsmenuuse_res, $cures, "mdesc")."</a><br />";
                                        endfor;
                                        if ($jsmenuuse_num>5):
                                            echo "<a style=\"cursor: pointer;\" id=\"showmore\" onclick=\"document.getElementById('hidemore').style.display = 'block'; document.getElementById('showmore').style.display = 'none';\" >".($jsmenuuse_num-5)." weitere ..</a>";
                                            echo "<span id=\"hidemore\" style=\"display: none;\">";
                                            for($cures=5; $cures<$jsmenuuse_num; $cures++):
                                                echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".mysql_result($jsmenuuse_res, $cures, "mid")."\">".mysql_result($jsmenuuse_res, $cures, "mdesc")."</a><br />";
                                            endfor;
                                            echo "</span>";
                                        endif;
                                    endif;
                                    
                                    echo "</td>";
                                    // action fields
                                    echo "<td class='col-md-1 text-right'>";
                                    // edit call form
                                    echo "<form name=\"edit_jsfolder_".$jfv['id']."\" id=\"edit_jsfolder_".$jfv['id']."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
                                    echo "<input name=\"op\" type=\"hidden\" value=\"editfolder\" />";
                                    echo "<input name=\"id\" type=\"hidden\" value=\"".$jfv['id']."\" />";
                                    echo "</form>\n";
                                    
                                    echo "<a href=\"#\" onClick=\"document.getElementById('edit_jsfolder_".$jfv['id']."').submit();\"><i class='far fa-pencil-alt fa-btn'></i></a> ";
                                    
                                    echo "<a href=\"#\" onClick=\"document.getElementById('edit_removefolder_".$jfv['id']."').submit();\"><i class='fa fa-trash fa-btn'></i></a> ";
                                    
                                    echo "</td>";
                                    echo "</tr>";
                                endforeach; ?>
                                </tbody>
                            </table>
                            <?php } else { echo '<p>'.returnIntLang('js no folders found').'<p>'; } ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('js upload folder zip'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <form name="uploadfolder_js" id="uploadfolder_js" method="post" enctype="multipart/form-data">
                            <p><input name="uploadfolder" type="file" id="dropify-uploadfolder" data-height="100" data-allowed-file-extensions="zip tar tgz" /></p>
                            <p class="submitter" style="display: none;"><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('str do upload', false); ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            
            // find js-FILES
            $jsfiles_sql = "SELECT * FROM `javascript` WHERE `cfolder` = `lastchange` AND `file` != '' ORDER BY `describ`";
            $jsfiles_res = doSQL($jsfiles_sql);
            
            ?>
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-heading primary">
                            <h3 class="panel-title"><?php echo returnIntLang('js existingfiles'); ?> <span class="badge inline-badge"><?php echo $jsfiles_res['num']; ?></span></h3>
                        </div>
                        <div class="panel-body">
                            <?php if ($jsfiles_res['num']>0) { ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo returnIntLang('str description'); ?> [ <?php echo returnIntLang('str filename'); ?> ]</th>
                                        <th><?php echo returnIntLang('str usage'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jsfiles_res['set'] AS $cfk => $cfv): ?>
                                    <tr>
                                        <td class='col-md-8'><?php 
                                        echo "<a style=\"cursor: pointer;\" onClick=\"document.getElementById('edit_design_".$cfk."').submit();\">".$cfv['describ']." [ ".$cfv['file'].".js ]</a>"; ?></td>
                                        <td class='col-md-3'><?php
                                        $jsuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_styles` AS rtc, `templates` AS t WHERE rtc.`templates_id` = t.`id` AND rtc.`stylesheets_id` = ".intval($cfv['id']);
                                        $jsuse_res = doSQL($jsuse_sql);
                                        if ($jsuse_res['num']>0):
                                            for($cures=0; $cures<$jsuse_res['num']; $cures++):
                                                echo "<a href=\"./templates.php?op=edit&id=".mysql_result($jsuse_res, $cures, "tid")."\">".setUTF8(mysql_result($jsuse_res, $cures, "tname"))."</a><br />";
                                            endfor;
                                        else:
                                            echo returnIntLang('str no usage', false);
                                        endif;
                                        ?></td>
                                        <td class="col-md-1 text-right"><?php
                                        echo "<a onClick=\"document.getElementById('edit_design_".$cfk."').submit();\"><i class='fa fa-pencil-alt fa-btn'></i></a> ";
                                        if ($jsuse_res['num']==0):
                                            echo " <a onclick=\"return confirmDelete('".$cfv['describ']."', ".intval($cfv['id']).");\" ><i class='fa fa-trash fa-btn'></i></a>";
                                        else:
                                            echo " <i class='fa fa-trash fa-disabled fa-btn'></i>";
                                        endif;
                                        echo "\t<form name=\"edit_design_".$cfk."\" id=\"edit_design_".$cfk."\" method=\"post\">";
                                        echo "<input name=\"op\" id=\"\" type=\"hidden\" value=\"edit\" />";
                                        echo "<input name=\"id\" id=\"\" type=\"hidden\" value=\"".intval($cfv['id'])."\" />";
                                        echo "</form>\n";
                                        ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                </tbody>    
                            </table>
                            <?php } else { echo '<p>'.returnIntLang('js no files found').'<p>'; } ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('js upload files'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <form name="uploadfile_js" id="uploadfile_js" method="post" enctype="multipart/form-data">
                            <p><input name="uploadfile" type="file" id="dropify-uploadfile" data-height="100" data-allowed-file-extensions="js" /></p>
                            <p class="submitter" style="display: none;"><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('str do upload', false); ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?op=edit&id=0" class="btn btn-primary"><?php echo returnIntLang('js createnewjs', false); ?></a></p>
            <form name="deletejs" id="deletejs" method="post">
                <input type="hidden" name="op" value="deletejs" />
                <input type="hidden" name="id" id="deleteid" value="" />
            </form>
            <?php endif; 
            // end of non edit
            
            // begin editing folder
            if (isset($_REQUEST['op']) && isset($_REQUEST['id']) && $_REQUEST['op']=='editfolder' && intval($_REQUEST['id'])>0) {
                
                // editing js folder contents
                
                $jsfolder_sql = "SELECT `id`, `cfolder`, `describ`, `scriptcode` FROM `javascript` WHERE `id` = ".intval($_REQUEST['id']);
                $jsfolder_res = doSQL($jsfolder_sql);

                if ($jsfolder_res['num']!=0):
                    $describ = $jsfolder_res['set'][0]['describ'];
                    $cfolder = $jsfolder_res['set'][0]['cfolder'];
                    $usedones = unserializeBroken($jsfolder_res['set'][0]['scriptcode']);
                endif;
            
                ?>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscriptfolder" name="formscriptfolder">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading primary">
                                <h3 class="panel-title"><?php echo returnIntLang('js editfolder'); ?></h3>
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
                                        <?php echo returnIntLang('js folderfiles'); ?>
                                    </div>
                                    <div class="col-md-10">
                                        <?php
                                        
                                        $path = DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR.$cfolder.DIRECTORY_SEPARATOR;
                                        $subs = dirList($path, DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."script".DIRECTORY_SEPARATOR, true, false);
                                        if (is_array($subs) && count($subs)>0) {
                                            array_unshift($subs, $path);
                                        } else {
                                            $subs = array($path);
                                        }
                                        $usefiles = array();
                                        foreach ($subs AS $sdk => $sdv) {
                                            $sf = scanfiles(cleanPath($sdv));
                                            if (is_array($sf)) {
                                                foreach ($sf AS $sfk => $sfv) {
                                                    $usefiles[] = cleanPath(str_replace($path, '/', $sdv."/".$sfv));
                                                }
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
                                            echo "<div class='dd' id='sortjsfolder'><ol class='dd-list' style='margin: 0px; margin-bottom: 10px; padding: 0px; list-style-type: none;'>";
                                            foreach ($usefiles AS $key => $value):
                                                echo "<li data-id='sortjsfolder_".$key."' class='dd-item li-btn btn-sm ".((is_array($usedones) && in_array($value, $usedones))?'btn-info':'btn-default')."'>";
                                                echo "<input type='checkbox' name='scriptcode[]' id='usefiles_".$key."' value='".$value."' ";
                                                if (is_array($usedones) && in_array($value, $usedones)):
                                                    echo " checked='checked' ";
                                                endif;
                                                echo " /> &nbsp; <span style='cursor: move;' class='dd-move'>".$value."</span></li>";
                                            endforeach;
                                            echo "</ol></div>";

                                        else:
                                            echo "<p>".returnIntLang('js folder doesnt exist')."</p>";
                                        endif;

                                        ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p><?php echo returnIntLang('js move activated files to correct order by drag and drop'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <script type="text/javascript" language="javascript" charset="utf-8">
		
                $(function() {
                    $("#sortjsfolder").nestable({
                        handleClass: 'dd-move',
                        maxDepth: 1,
                    });
                });

                </script>
                <input type="hidden" name="id" value="<?php echo intval($_REQUEST['id']); ?>" />
                <input type="hidden" name="op" value="savefolder" />
                <p><a onclick="document.getElementById('formscriptfolder').submit();" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a> <a href="?" class="btn btn-warning"><?php echo returnIntLang('str cancel'); ?></a></p>
                </form>
            <?php } 
            // end of edit folder 
            
            // begin editing file/sourcecode
            if (isset($_REQUEST['op']) && isset($_REQUEST['id']) && $_REQUEST['op']=='edit') {
                
                // editing js file contents    
                // setup empty values if new or none found by id
                $id = intval($_REQUEST['id']);
                $describ = '';
                $file = '';
                $scriptcode = '';
                if (isset($sourcefile) && $sourcefile===true) {
                    $readsource = DOCUMENT_ROOT.base64_decode(trim($_REQUEST['sourcefile']));
                    if (is_file($readsource)) {
                        $sourcename = substr($readsource, (strrpos($readsource, "/")+1));
                        $describ = $file = substr($sourcename, 0, -3);
                        $scriptcode = file_get_contents($readsource);
                    }
                }
                else if (isset($readsource) && $readsource!==false) {
                    $describ = $file = substr(basename($readname), 0, -3);
                    $scriptcode = $readsource;
                }
                else if (intval($_REQUEST['id'])>0):
                    $javascript_sql = "SELECT `id`, `file`, `describ`, `scriptcode` FROM `javascript` WHERE `id` = ".intval($_REQUEST['id']);
		            $javascript_res = doSQL($javascript_sql);
                    if ($javascript_res['num']>0):
                        $id = intval($_REQUEST['id']);
                        $describ = $javascript_res['set'][0]['describ'];
                        $file = $javascript_res['set'][0]['file'];
                        $scriptcode = stripslashes($javascript_res['set'][0]['scriptcode']);
                    endif;
                endif;

                ?>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscript">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading primary">
                                <h3 class="panel-title"><?php if($id>0): echo returnIntLang('js editfile'); else: echo returnIntLang('js createfile'); endif; ?></h3>
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
                                            <textarea name="scriptcode" id="scriptcode_area" rows="10" class="form-control allowTabChar" wrap="off"><?php
                                            $scriptrows = explode("\n",$scriptcode);
                                            for ($r=0;$r<count($scriptrows);$r++) {
                                                if (trim($scriptrows[$r])!="") {
                                                    if (!strstr($scriptrows[$r],"{")) {
                                                        echo "\t";
                                                    }
                                                    echo trim($scriptrows[$r])."\n";
                                                    if (strstr($scriptrows[$r],"}")) {
                                                        echo "\n";
                                                    }
                                                }
                                            } ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="op" value="save" /><input type="hidden" name="id" value="<?php echo $id; ?>" />
                <p><button onclick="document.getElementById('formscript').submit();" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></button> <a href="?" class="btn btn-warning"><?php echo returnIntLang('str cancel'); ?></a></p>
                </form>
            <?php }
            // end of edit file ?>
        </div>
    </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script language="JavaScript" type="text/javascript">
<!--

function confirmDelete(scriptname, jsid) {
    if (confirm('<?php echo returnIntLang('js removemessage1', false); ?> ' + scriptname + ' <?php echo returnIntLang('js removemessage2', false); ?>')) {
        document.getElementById('deleteid').value = jsid;
        document.getElementById('deletejs').submit();
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
        $('#uploadfolder_js .submitter').show();
        });
    drFolder.on('dropify.afterClear', function(event, element) {
        $('#uploadfolder_js .submitter').hide();
        });
    
    var drFile = $('#dropify-uploadfile').dropify({messages: { default: 'Upload JS' }});
    drFile.on('dropify.afterReady', function(event, element) {
        $('#uploadfile_js .submitter').show();
        });
    drFile.on('dropify.afterClear', function(event, element) {
        $('#uploadfile_js .submitter').hide();
        });
    })
    
// -->
</script>

<?php include ("./data/include/footer.inc.php"); ?>