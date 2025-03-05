<?php
/**
 * @description global editor properties
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-01-19
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require("./data/include/usestat.inc.php");
require("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-gears',returnIntLang('menu manage'),returnIntLang('menu manage editor'));
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['mpos'] = 1;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js'
    );
// second includes ---------------------------
require("./data/include/checkuser.inc.php");
require("./data/include/errorhandler.inc.php");
require("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

// save editor prefs
if (isset($_POST['save_data'])) {
	if (intval($_POST['backupsteps'])<3) { $_POST['backupsteps'] = 3; }
	if (intval($_POST['autologout'])<15) { $_POST['autologout'] = 15; }
	if (intval($_POST['loginfails'])<3) { $_POST['loginfails'] = 3; }
	// replace spaces in thumbsize, pdfscalepreview, hiddenmedia
	if(isset($_POST['thumbsize'])) $_POST['thumbsize'] = str_replace(" ", "", $_POST['thumbsize']);
	if(isset($_POST['pdfscalepreview'])) $_POST['pdfscalepreview'] = str_replace(" ", "", $_POST['pdfscalepreview']);
	if(isset($_POST['hiddenmedia'])) $_POST['hiddenmedia'] = str_replace(" ", "", $_POST['hiddenmedia']);
    if(isset($_POST['showhiddenmedia'])) $_POST['showhiddenmedia'] = str_replace(" ", "", $_POST['showhiddenmedia']);
    if(isset($_POST['hiddenimages'])) $_POST['hiddenimages'] = str_replace(" ", "", $_POST['hiddenimages']);
    if(isset($_POST['hiddendownload'])) $_POST['hiddendownload'] = str_replace(" ", "", $_POST['hiddendownload']);
	// run db entry
	foreach ($_POST AS $key => $value) {
		if ($key!="save_data") {
			doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".$key."'");
			if (is_array($value)) {
                doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL(serialize($value))."'");
            }
			else {
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL($value)."'");
			}
		}
	}
	addWSPMsg('resultmsg', returnIntLang('editorprefs saved', false));
}

// get siteinfo facts from saved file
require ("./data/include/siteinfo.inc.php");

// create rotots.txt if needed
if ((isset($sitedata['wsprobots']) && $sitedata['wsprobots']==1) || (isset($sitedata['disabledrobots']) && trim($sitedata['disabledrobots']!=''))) {
	$disallow = array("# robots.txt page ".$sitedata['siteurl']."\n", "User-agent: *");
	if (isset($sitedata['wsprobots']) && $sitedata['wsprobots']==1) {
        $disallow[] = str_replace("//", "/", str_replace("//", "/", "Disallow: /".$_SESSION['wspvars']['wspbasedir']."/"));
    }
	if (isset($sitedata['disabledrobots']) && trim($sitedata['disabledrobots']!='')) {
		$disdir = explode(",", $sitedata['disabledrobots']);
		foreach ($disdir AS $dv) {
			if (trim($dv)!='') {
				$disallow[] = str_replace("//", "/", str_replace("//", "/", "Disallow: /".trim($dv)."/"));
			}
		}	
	}
	$disallow = implode("\n", $disallow);
	// create ftp-connect
    $ftp = doFTP();
	if ($ftp):
		// create file content
		$fh = fopen("./tmp/robots.txt", "w+");
		fwrite($fh, $disallow);
		fclose($fh);
		// copy file to structure
		ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir'].'/robots.txt', $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/robots.txt', FTP_BINARY);
		@ftp_close($ftp);
		unlink("./tmp/robots.txt");
	endif;
}

$sitedata = getWSPProperties();

// setup siteinfo facts
$sitedata['siteurl'] = isset($sitedata['siteurl'])?trim(str_replace("http://", "", $sitedata['siteurl'])):$_SERVER['HTTP_HOST'];
$sitedata['devurl'] = isset($sitedata['devurl'])?trim(str_replace("http://", "", $sitedata['devurl'])):$_SERVER['HTTP_HOST'];
$sitedata['devurl'] = trim($sitedata['devurl'])!=''?$sitedata['devurl']:$sitedata['siteurl'];
$sitedata['backupsteps'] = isset($sitedata['backupsteps'])?intval($sitedata['backupsteps']):3;
$sitedata['backupsteps'] = intval($sitedata['backupsteps'])>=3?$sitedata['backupsteps']:3;
$sitedata['shownotice'] = isset($sitedata['shownotice'])?intval($sitedata['shownotice']):2;
$sitedata['mailclass'] = isset($sitedata['mailclass'])?intval($sitedata['mailclass']):1;
$sitedata['deletedmenu'] = isset($sitedata['deletedmenu'])?intval($sitedata['deletedmenu']):0;



if (!(isset($sitedata['autologout'])) || intval($sitedata['autologout'])<15) { $sitedata['autologout'] = 15; }
if (!(isset($sitedata['cookiedays'])) || intval($sitedata['cookiedays'])<1) { $sitedata['cookiedays'] = 1; }

// include head ------------------------------
include("./data/include/header.inc.php");
include("./data/include/navbar.inc.php");
include("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('editorprefs headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('editorprefs desc'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg($sitedata['shownotice']); ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmprefs" style="margin: 0px;">
            <div class="row">
                <div class="col-md-12">
                     <div class="panel panel-tab">
                        <div class="panel-heading">
                            <ul class="nav nav-tabs pull-left">
                                <li class="active"><a href="#editorprefs_generics" data-toggle="tab"><i class="fas fa-pencil-ruler"></i> <?php echo returnIntLang('editorprefs environment', false); ?></a></li>
                                <li><a href="#editorprefs_output" data-toggle="tab"><i class="fa fa-globe"></i> <?php echo returnIntLang('editorprefs output', false); ?></a></li>
                                <li><a href="#editorprefs_smtp" data-toggle="tab"><i class="fa fa-envelope"></i> <?php echo returnIntLang('editorprefs mailsetting', false); ?></a></li>
                                <li><a href="#editorprefs_workflow" data-toggle="tab"><i class="fas fa-cogs"></i> <?php echo returnIntLang('editorprefs workflow', false); ?></a></li>
                                <li><a href="#editorprefs_files" data-toggle="tab"><i class="fa fa-image"></i> <?php echo returnIntLang('editorprefs files', false); ?></a></li>
                                <li><a href="#editorprefs_security" data-toggle="tab"><i class="fa fa-lock"></i> <?php echo returnIntLang('editorprefs security', false); ?></a></li>
                            </ul>
                            <h3 class="panel-title">&nbsp;</h3>
                        </div>
                        <div class="panel-body" >
                            <div class="tab-content no-padding">
                                <div class="tab-pane fade in active" id="editorprefs_generics">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs sslmode'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="sslmode" value="0" />
                                                    <input type="checkbox" name="sslmode" id="sslmode" <?php if(array_key_exists('sslmode', $sitedata) && intval($sitedata['sslmode'])==1) echo "checked=\"checked\""; ?> value="1" class="form-control" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs devurl'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" name="devurl" id="devurl" value="<?php echo $sitedata['devurl']; ?>" class="form-control" placeholder="<?php echo returnIntLang('editorprefs devurl without http://', false); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs base template'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <?php

                                                $stdtempl = doSQL("SELECT `id`, `name` FROM `templates` ORDER BY `name`");
                                                $tmplbuf = '';
                                                foreach ($stdtempl['set'] AS $stk => $stv) {
                                                    $tmplbuf.= "<option value=\"".$stv['id']."\"";
                                                    if ($stv['id']==intval($sitedata['templates_id'])) {
                                                        $tmplbuf.= " selected";
                                                    }
                                                    $tmplbuf.= ">".$stv['name']."</option>";
                                                }

                                                if ($tmplbuf!='') {
                                                    echo '<select name="templates_id" class="form-control singleselect fullwidth">';
                                                    echo $tmplbuf;
                                                    echo '</select>';
                                                } else {
                                                    echo '<p>'.returnIntLang('str none avaiable').'</p>';
                                                }

                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs wspstyle'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                            <?php

                                            /* read all files from /media/layout/ and find files with @type design */
                                            $funcfolder = array();
                                            $designfiles = array();
                                            $functiondir = opendir (DOCUMENT_ROOT."/".WSP_DIR."/media/layout/");
                                            while ($entry = readdir($functiondir)):
                                                if (stristr($entry, ".css") && stristr($entry, "wsp7-") && !stristr($entry, "night")):
                                                    $cssfile[] = $entry;
                                                endif;
                                            endwhile;
                                            closedir ($functiondir);
                                            sort ($cssfile);
                                            foreach($cssfile AS $key => $fileinfo):
                                                $filearray = file(DOCUMENT_ROOT."/".WSP_DIR."/media/layout/".$fileinfo);
                                                $designfiles[$key]['file'] = str_replace(".css", "", $fileinfo);
                                                for ($fa=1; $fa<count($filearray); $fa++):
                                                    if (substr(trim($filearray[$fa]),0,3)=="* @"):
                                                        if (substr(trim($filearray[$fa]),3,11)=="description"):
                                                            $designfiles[$key]['desc'] = trim(substr(trim($filearray[$fa]),14));
                                                        elseif (substr(trim($filearray[$fa]),3,4)=="type"):
                                                            $designfiles[$key]['type'] = trim(substr(trim($filearray[$fa]),7));
                                                        elseif (substr(trim($filearray[$fa]),3,7)=="version"):
                                                            $designfiles[$key]['vers'] = trim(substr(trim($filearray[$fa]),10));
                                                        endif;
                                                    endif;
                                                    if ($fa>20 || substr(trim($filearray[$fa]),0,2)=="*/"):
                                                        $fa = count($filearray);
                                                    endif;
                                                endfor;
                                                if (!(key_exists('type', $designfiles[$key])) || (key_exists('type', $designfiles[$key]) && trim($designfiles[$key]['type'])=="")):
                                                    unset($designfiles[$key]);
                                                endif;
                                            endforeach;
                                            if (count($designfiles)>1) {
                                                echo "<select name=\"wspstyle\" class=\"form-control singleselect\">";
                                                foreach ($designfiles AS $dkey => $dvalue):
                                                echo "<option value=\"".$dvalue['file']."\" ";
                                                if ($sitedata['wspstyle']==$dvalue['file']): echo " selected=\"selected\""; endif;
                                                echo ">";
                                                echo trim(trim($dvalue['desc'])." ".trim($dvalue['vers']));
                                                echo "</option>";
                                                endforeach;
                                                echo "</select>";
                                            } else {
                                                if (count($designfiles)>0) {
                                                    echo '<input type="hidden" name="wspstyle" value="'.$designfiles[0]['file'].'" />';
                                                }
                                                echo returnIntLang('editorprefs wspstyle not found', true);
                                            }

                                            ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs wspbaselang'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select name="wspbaselang" class="form-control singleselect">
                                                    <?php foreach($_SESSION['wspvars']['locallanguages'] AS $llkey => $llvalue): 
                                                        echo "<option value=\"".$llkey."\" ";
                                                        if (array_key_exists('wspbaselang', $sitedata) && $sitedata['wspbaselang']==$llkey): echo " selected=\"selected\" "; endif;
                                                        echo ">".$llvalue."</option>";
                                                    endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs extendedmenu'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="extendedmenu" value="0" />
                                                    <input type="checkbox" name="extendedmenu" id="extendedmenu" <?php if(array_key_exists('extendedmenu', $sitedata) && intval($sitedata['extendedmenu'])==1) echo "checked=\"checked\""; ?> value="1" class="form-control" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs showlegend'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="showlegend" value="0" />
                                                    <input type="checkbox" name="showlegend" id="showlegend" <?php if(array_key_exists('showlegend', $sitedata) && intval($sitedata['showlegend'])==1) echo "checked=\"checked\""; ?> value="1" class="form-control" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs shownotice inline'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="shownotice" value="0" />
                                                    <input type="checkbox" name="shownotice" id="shownotice" <?php if(array_key_exists('shownotice', $sitedata) && intval($sitedata['shownotice'])==1) echo "checked=\"checked\""; ?> value="1" class="form-control" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs nightmode'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="nightmode" value="0" />
                                                    <input type="checkbox" name="nightmode" id="nightmode" <?php if(array_key_exists('nightmode', $sitedata) && intval($sitedata['nightmode'])==1) echo "checked=\"checked\""; ?> value="1" class="form-control" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs nightmode time'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <select name="startnight" class="form-control singleselect">
                                                        <option value="17" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==17)?'selected="selected"':''); ?>>17:00</option>
                                                        <option value="18" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==18 || !(isset($sitedata['startnight'])))?'selected="selected"':''); ?>>18:00</option>
                                                        <option value="19" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==19)?'selected="selected"':''); ?>>19:00</option>
                                                        <option value="20" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==20)?'selected="selected"':''); ?>>20:00</option>
                                                        <option value="21" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==21)?'selected="selected"':''); ?>>21:00</option>
                                                        <option value="22" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==22)?'selected="selected"':''); ?>>22:00</option>
                                                        <option value="23" <?php echo ((isset($sitedata['startnight']) && intval($sitedata['startnight'])==23)?'selected="selected"':''); ?>>23:00</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-1">-</div>
                                                <div class="col-md-5">
                                                    <select name="endnight" class="form-control singleselect">
                                                        <option value="5" <?php echo ((isset($sitedata['endnight']) && intval($sitedata['endnight'])==5)?'selected="selected"':''); ?>>05:00</option>
                                                        <option value="6" <?php echo ((isset($sitedata['endnight']) && intval($sitedata['endnight'])==6)?'selected="selected"':''); ?>>06:00</option>
                                                        <option value="7" <?php echo ((isset($sitedata['endnight']) && intval($sitedata['endnight'])==7)?'selected="selected"':''); ?>>07:00</option>
                                                        <option value="8" <?php echo ((isset($sitedata['endnight']) && intval($sitedata['endnight'])==8 || !(isset($sitedata['endnight'])))?'selected="selected"':''); ?>>08:00</option>
                                                        <option value="9" <?php echo ((isset($sitedata['endnight']) && intval($sitedata['endnight'])==9)?'selected="selected"':''); ?>>09:00</option>
                                                        <option value="10" <?php echo ((isset($sitedata['endnight']) && intval($sitedata['endnight'])==10)?'selected="selected"':''); ?>>10:00</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade in active" id="editorprefs_output">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs liveurl'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" name="liveurl" id="liveurl" value="<?php echo isset($sitedata['liveurl'])?$sitedata['liveurl']:''; ?>" class="form-control" placeholder="<?php echo returnIntLang('editorprefs liveurl without http://', false); ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <span><?php echo returnIntLang('editorprefs sslmode'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="sslmode" value="0" />
                                                    <input type="checkbox" name="sslmode" id="sslmode" <?php if(array_key_exists('sslmode', $sitedata) && intval($sitedata['sslmode'])==1) echo "checked=\"checked\""; ?> value="1" class="form-control" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <!--
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs output ftpcon'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select name="ftp_output" class="form-control singleselect fullwidth">
                                                    <option>list of ftp-connections</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    -->
                                </div>
                                <div class="tab-pane fade" id="editorprefs_smtp">
                                    <?php if(defined('SMTP_HOST') && defined('SMTP_USER') && defined('SMTP_PASS') && defined('SMTP_PORT')): ?>
                                        <div class="row">
                                            <div class="col-md-3"><?php echo returnIntLang('editorprefs mailsetting method'); ?></div>
                                            <div class="col-md-9">
                                                <select name="mailclass" class="form-control singleselect">
                                                    <option value="0" <?php if($sitedata['mailclass']==0): echo "selected"; endif; ?>><?php echo returnIntLang('editorprefs standardmail'); ?></option>
                                                    <option value="1" <?php if($sitedata['mailclass']==1): echo "selected"; endif; ?>><?php echo returnIntLang('editorprefs smtpmail'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p><?php echo returnIntLang('editorprefs mail will be sent by mail()-function'); ?></p>
                                        <input type="hidden" name="mailclass" value="0" style="width: 99%;">
                                    <?php endif; ?>
                                </div>
                                <div class="tab-pane fade" id="editorprefs_workflow">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs deleted structure', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="deletedmenu" name="deletedmenu" class="form-control singleselect">
                                                    <option value="0"><?php echo returnIntLang('editorprefs deleted structure stay', false); ?></option>
                                                    <option value="1" <?php if(isset($sitedata['deletedmenu']) && $sitedata['deletedmenu']=='1') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs deleted structure delete', false); ?></option>
                                                    <option value="2" <?php if($sitedata['deletedmenu']==2) echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs deleted structure index', false); ?></option>
                                                    <option value="3" <?php if($sitedata['deletedmenu']==3) echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs deleted structure hint', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs bind content visibility to menu', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="bindcontentview" value="0" /><input type="checkbox" name="bindcontentview" id="bindcontentview" <?php if(isset($sitedata['bindcontentview']) && intval($sitedata['bindcontentview'])==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs hidden structure', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="hiddenmenu" name="hiddenmenu" class="form-control singleselect">
                                                    <option value="1" <?php if(isset($sitedata['hiddenmenu']) && $sitedata['hiddenmenu']=='1') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs hidden structure hide contents', false); ?></option>
                                                    <option value="2" <?php if(isset($sitedata['hiddenmenu']) && $sitedata['hiddenmenu']=='2') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs hidden structure disable page', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs nocontent parsing', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="nocontentmenu" name="nocontentmenu" class="form-control singleselect">
                                                    <option value="0" <?php if($sitedata['nocontentmenu']=='0') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs nocontent noparse', false); ?></option>
                                                    <option value="1" <?php if($sitedata['nocontentmenu']=='1') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs nocontent nocontent', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs replacechars', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select name="filereplacer" id="filereplacer" class="form-control singleselect">
                                                    <option value="-"<?php if($sitedata['filereplacer']=='-') echo ' selected="selected"' ?>>-</option>
                                                    <option value="_"<?php if($sitedata['filereplacer']=='_') echo ' selected="selected"' ?>>_</option>
                                                    <option value="."<?php if($sitedata['filereplacer']=='.') echo ' selected="selected"' ?>><?php echo returnIntLang('str remove', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                           <span data-toggle="tooltip" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang('editorprefs parsedirectories help', false); ?>"><?php echo returnIntLang('editorprefs parsedirectories', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue">
                                                <label>
                                                    <input type="hidden" name="parsedirectories" value="0" />
                                                    <input type="checkbox" name="parsedirectories" id="parsedirectories" <?php if(intval($sitedata['parsedirectories'])==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <span data-toggle="tooltip" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang('editorprefs autopublish structure help', false); ?>"><?php echo returnIntLang('editorprefs autopublish structure', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="autoparsestructure" value="0" /><input type="checkbox" name="autoparsestructure" id="autoparsestructure" <?php if(intval($sitedata['autoparsestructure'])==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                           <span data-toggle="tooltip" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang('editorprefs autopublish content help', false); ?>"><?php echo returnIntLang('editorprefs autopublish content', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="autoparsecontent" value="0" /><input type="checkbox" name="autoparsecontent" id="autoparsecontent" <?php if(intval($sitedata['autoparsecontent'])==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs stripslashes', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input name="stripslashes" type="text" class="form-control" id="stripslashes" value="<?php echo intval($sitedata['stripslashes']); ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs backup steps', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input name="backupsteps" type="text" class="form-control" id="backupsteps" value="<?php echo intval($sitedata['backupsteps']); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs no auto index', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="noautoindex" value="0" /><input type="checkbox" name="noautoindex" id="noautoindex" <?php if(intval($sitedata['noautoindex'])==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs mask email@', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input name="maskmail" type="text" class="form-control" id="maskmail" value="<?php if (isset($sitedata['maskmail'])) echo ($sitedata['maskmail']); ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs contentedit container pref', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="containerpref" id="containerpref" class="form-control singleselect">
                                                <option value="0" <?php echo (isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==0)?' selected="selected" ':''; ?>>SECTION</option>
                                                <option value="1" <?php echo (isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==1)?' selected="selected" ':''; ?>>DIV</option>
                                                <option value="2" <?php echo (isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==2)?' selected="selected" ':''; ?>>SPAN</option>
                                                <option value="3" <?php echo (isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==3)?' selected="selected" ':''; ?>>LI</option>
                                                <option value="4" <?php echo (isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==4 || !(isset($sitedata['containerpref'])))?' selected="selected" ':''; ?>><?php echo returnIntLang('str none'); ?></option>
                                                <option value="5" <?php echo (isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==5)?' selected="selected" ':''; ?>><?php echo returnIntLang('contentedit special container combine'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="editorprefs_files">   
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs display mediafiles', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="displaymedia" name="displaymedia" class="form-control singleselect">
                                                    <option value="list"><?php echo returnIntLang('editorprefs display mediafiles list', false); ?></option>
                                                    <option value="box" <?php if(key_exists('displaymedia', $sitedata) && $sitedata['displaymedia']=='box') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs display mediafiles box', false); ?></option>
                                                    <option value="box" <?php if(key_exists('displaymedia', $sitedata) && $sitedata['displaymedia']=='minibox') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs display mediafiles minibox', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs sort mediafiles', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="medialistsort" name="medialistsort" class="form-control singleselect">
                                                    <option value="name"><?php echo returnIntLang('editorprefs sort mediafiles name', false); ?></option>
                                                    <option value="size" <?php if($sitedata['medialistsort']=='size') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs sort mediafiles size', false); ?></option>
                                                    <option value="date" <?php if($sitedata['medialistsort']=='date') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs sort mediafiles date', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs autoscale preselect', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group form-group">
                                                <input type="text" id="autoscalepreselect" name="autoscalepreselect" placeholder="1024 x 768" value="<?php if(isset($sitedata['autoscalepreselect'])): echo str_replace("x", " x ", str_replace(" ", "", $sitedata['autoscalepreselect'])); endif; ?>" class="form-control" />
                                                <span id="show_sitetitle_length" class="input-group-addon">PX x PX</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs converting pdf to image', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group form-group">
                                                <input type="text" id="pdfscalepreview" name="pdfscalepreview" class="form-control" placeholder="800x600" value="<?php if(isset($sitedata['pdfscalepreview'])): echo str_replace(" ", "", $sitedata['pdfscalepreview']); endif; ?>" />
                                                <span id="show_sitetitle_length" class="input-group-addon">PX x PX</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs hold original', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="holdoriginalimages" value="0" /><input type="checkbox" name="holdoriginalimages" id="holdoriginalimages" <?php if(isset($sitedata['holdoriginalimages']) && $sitedata['holdoriginalimages']==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs thumbnail size', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group form-group">
                                                <input type="text" id="thumbsize" name="thumbsize" placeholder="200" value="<?php if(isset($sitedata['thumbsize'])): echo intval($sitedata['thumbsize']); endif; ?>" class="form-control" />
                                                <span id="show_sitetitle_length" class="input-group-addon">PX</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs overwrite files', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="overwriteuploads" name="overwriteuploads" class="form-control singleselect">
                                                    <option value="0"><?php echo returnIntLang('editorprefs dont overwrite uploads', false); ?></option>
                                                    <option value="1" <?php if(isset($sitedata['overwriteuploads']) && $sitedata['overwriteuploads']==1) echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs overwrite uploads', false); ?></option>
                                                    <option value="2" <?php if(isset($sitedata['overwriteuploads']) && $sitedata['overwriteuploads']==2) echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs overwrite unused uploads', false); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs strip filenames', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" id="stripfilenames" name="stripfilenames" placeholder="60" value="<?php if(isset($sitedata['stripfilenames'])): echo intval($sitedata['stripfilenames']); endif; ?>" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <span class="help" data-toggle="tooltip" data-original-title="<?php echo returnIntLang('editorprefs hidden media folders help', false); ?>"><?php echo returnIntLang('editorprefs hidden media folders', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" id="hiddenmedia" name="hiddenmedia" class="form-control" placeholder="thumbs, preview, originals" value="<?php if(isset($sitedata['hiddenmedia'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddenmedia'])); endif; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="help" data-toggle="tooltip" data-original-title="<?php echo returnIntLang('editorprefs show hidden media folders as download help', false); ?>"><?php echo returnIntLang('editorprefs show hidden media folders as download target', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" id="showhiddenmedia" name="showhiddenmedia" class="form-control" placeholder="<?php if(isset($sitedata['hiddenmedia'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddenmedia'])); endif; ?>" value="<?php if(isset($sitedata['showhiddenmedia'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['showhiddenmedia'])); endif; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <span class="help" data-toggle="tooltip" data-original-title="<?php echo returnIntLang('editorprefs hidden dropdown images folders help', false); ?>"><?php echo returnIntLang('editorprefs hidden dropdown images folders', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" id="hiddenimages" name="hiddenimages" class="form-control" placeholder="" value="<?php if(isset($sitedata['hiddenimages'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddenimages'])); endif; ?>" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="help" data-toggle="tooltip" data-original-title="<?php echo returnIntLang('editorprefs hidden dropdown download folders help', false); ?>"><?php echo returnIntLang('editorprefs hidden dropdown download folders', true); ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="text" id="hiddendownload" name="hiddendownload" class="form-control" placeholder="" value="<?php if(isset($sitedata['hiddendownload'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddendownload'])); endif; ?>" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="editorprefs_security">   
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs autologout', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="number" name="autologout" id="autologout" value="<?php echo intval($sitedata['autologout']); ?>" style="width: 5em;" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs loginfails', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                   <input type="number" name="loginfails" id="loginfails" value="<?php echo isset($sitedata['loginfails'])?intval($sitedata['loginfails']):0; ?>" class="form-control" />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php /*
                                    <div class="row" style="display: _none;">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs login cookie runtime', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group form-group">
                                                <input type="number" name="cookiedays" id="cookiedays" value="<?php echo intval($sitedata['cookiedays']); ?>" class="form-control" />
                                                <span id="show_sitetitle_length" class="input-group-addon"><?php echo returnIntLang('str days', true); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs cookie based autologin', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="cookielogin" value="0" /><input type="checkbox" name="cookielogin" id="cookielogin" <?php if($sitedata['cookielogin']==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    */ ?>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs forbidden filenames', true); ?>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group">
                                                <textarea name="nonames" id="nonames" rows="4" class="form-control full growingarea"><?php
                                                if (isset($sitedata['nonames'])) {
                                                    $forbiddentmp = implode(",", explode(";", $sitedata['nonames']));
                                                    $forbiddentmp = implode(",", explode(PHP_EOL, $forbiddentmp));
                                                    $forbiddentmp = explode(",", $forbiddentmp);
                                                    $forbiddentxt = array();
                                                    foreach ($forbiddentmp AS $fbk => $fbv) { if (trim($fbv)!='') { $forbiddentxt[] = trim($fbv); }}
                                                    echo implode(", ", $forbiddentxt);
                                                }
                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs errormessages submit to developers', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="errorreporting" value="0" /><input type="checkbox" name="errorreporting" id="errorreporting" <?php if($sitedata['errorreporting']==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs allow unsecure install', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="unsafemodinstall" value="0" /><input type="checkbox" name="unsafemodinstall" id="unsafemodinstall" <?php if($sitedata['unsafemodinstall']==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs disable wsp robots', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fancy-checkbox custom-bgcolor-blue form-group">
                                                <label>
                                                    <input type="hidden" name="wsprobots" value="0" /><input type="checkbox" name="wsprobots" id="wsprobots" <?php if(isset($sitedata['wsprobots']) &&  $sitedata['wsprobots']==1) echo "checked=\"checked\""; ?> value="1" />
                                                    <span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('editorprefs disable directories', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" id="disabledrobots" name="disabledrobots" class="form-control" placeholder="data, media" value="<?php if(isset($sitedata['disabledrobots'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['disabledrobots'])); endif; ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <input type="hidden" name="use_css" value="wsp">
                    <input type="hidden" name="defaultpublish" value="5" />
                    <p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
                </div>
            </div>
            </form>
	   </div>
    </div>
<!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->
<script>

$(document).ready(function() { 
    
    $('a[data-toggle="tab"]').on('click', function(e) {
        window.localStorage.setItem('editorprefstab', $(e.target).attr('href'));
    });
    var editorprefsTab = window.localStorage.getItem('editorprefstab');
    if (editorprefsTab) {
        $('a[data-toggle="tab"]').parent('li').removeClass('active');
        $('.tab-pane.active').removeClass('active');
        $('a[href="' + editorprefsTab + '"]').tab('show');
    }
    
    $('.singleselect').multiselect();
    
});
    

</script>
<?php require ("./data/include/footer.inc.php"); ?>