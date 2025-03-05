<?php
/**
 * global site-setup
 * @author stefan@covi.de
 * @since 4.0
 * @version 7.0
 * @lastchange 2019-11-27
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define page params ------------------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-wrench',returnIntLang('menu siteprefs'),returnIntLang('menu siteprefs redirects'));
$_SESSION['wspvars']['menuposition'] = 'siteprops';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['mpos'] = 3;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

$edit_mobile = false;
$edit_url = false;
$edit_path = false;
$edit_var = false;

// init information for structure display mode
if (!(isset($_SESSION['wspvars']['sdm']))): $_SESSION['wspvars']['sdm'] = 0; endif;
if (isset($_REQUEST['sdm'])): $_SESSION['wspvars']['sdm'] = intval($_REQUEST['sdm']); endif;

/* define page specific functions ------------ */
if (isset($_POST['save_data'])) {
	if ($_POST['save_data']=="mobile") {
		$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = 'mobile_pages'";
		doSQL($deletedata_sql);
		$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = 'mobile_path'";
		doSQL($deletedata_sql);
		$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = 'mobile_pages', `varvalue` = '".intval($_POST['mobile_pages'])."'";
		doSQL($insertdata_sql);
		$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = 'mobile_path', `varvalue` = '".escapeSQL(trim($_POST['mobile_path']))."'";
		doSQL($insertdata_sql);
        $edit_mobile = true;
	}
	if ($_POST['save_data']=="url") {
		$i = 0;
		foreach ($_POST AS $key => $value) {
			if ($key!="save_data") {
				if (trim($value['url'])!='') {
					$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'";
					doSQL($deletedata_sql);
					$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = 'url_forward_".$i."', `varvalue` = '".escapeSQL(serialize($value))."'";
					doSQL($insertdata_sql);
					$i++;
                }
				else if (trim($value['url'])=='' || intval($value['target'])==0) {
					doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'");
                }
			}
		}
        $edit_url = true;
		addWSPMsg('noticemsg', returnIntLang('redirect saved urlproperties', false));
	}
	if ($_POST['save_data']=="var") {
		$i = 0;
		foreach ($_POST AS $key => $value) {
			if ($key!="save_data") {
				if (trim($value['varname'])!='' && intval($value['target'])>0) {
					$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'";
					doSQL($deletedata_sql);
					$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = 'var_forward_".$i."', `varvalue` = '".escapeSQL(serialize($value))."'";
					doSQL($insertdata_sql);
					addWSPMsg('noticemsg', returnIntLang('redirect saved varproperties1')." \"<strong>".trim($value['varname'])."=".trim($value['varvalue'])."</strong>\" ".returnIntLang('redirect saved varproperties2'));
					$i++;
				}
                else if (trim($value['varname'])=='' || intval($value['target'])==0) {
					$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'";
					doSQL($deletedata_sql);
				}
			}
		}
        $edit_var = true;
	}
    if ($_POST['save_data']=="path") {
        // remove OLD fashioned entries in wspproperties
        doSQL("DELETE FROM `wspproperties` WHERE `varname` LIKE 'redirect_path_%'");
        $i=0; $uni = array();
        foreach ($_POST['redirect_path'] AS $key => $value) {
            if (trim($value['path'])!='' && intval($value['target'])>0) {
                if (!(in_array(array($value['parent']=>$value['path']), $uni))) {
                    // check for existing filename in database
                    $nameexists = getNumSQL("SELECT `mid` FROM `menu` WHERE `connected` = ".intval($value['parent'])." AND `filename` = '".escapeSQL(urltext($value['path']))."' AND `mid` != ".intval($value['mid']));
                    if ($nameexists>0) {
                        // path already exists
                        addWSPMsg('errormsg', returnIntLang('redirect double path pathproperties1')." \"<strong>/".trim($value['path'])."/</strong>\" ".returnIntLang('redirect double path pathproperties2'));
                    }
                    else {
                        if (intval($value['mid'])>0) {
                            // update existing menupoint to new values
                            $cpath_sql = "UPDATE `menu` SET `editable` = 0, `breaktree` = 1, `visibility` = 0, `description` = 'Forwardpath  ".escapeSQL(urltext($value['path']))."', `internlink_id` = ".intval($value['target']).", `connected` = ".intval($value['parent']).", `filename` = '".escapeSQL(urltext($value['path']))."' WHERE `mid` = ".intval($value['mid']);
                            $cpath_res = doSQL($cpath_sql);
                            if ($cpath_res['aff']==1) {
                                addWSPMsg('resultmsg', returnIntLang('redirect updated pathproperties1')." \"<strong>".trim(fileNamePath(intval($value['mid']),0,0,1))."</strong>\" ".returnIntLang('redirect updated pathproperties2'));
                            }
                        }
                        else {
                            // create a new menupoint with values
                            $cpath_sql = "INSERT INTO `menu` SET `editable` = 0, `breaktree` = 1, `position` = 0, `visibility` = 0, `description` = 'Forwardpath  ".escapeSQL(urltext($value['path']))."', `templates_id` = 0, `internlink_id` = ".intval($value['target']).", `level` = 0, `connected` = ".intval($value['parent']).", `filename` = '".escapeSQL(urltext($value['path']))."', `contentchanged` = 0, `changetime` = ".time().", `isindex` = 0, `trash` = 0, `structurechanged` = ".time().", `menuchangetime` = ".time().", `lastchange` = ".time();
                            $cpath_res = doSQL($cpath_sql);
                            if ($cpath_res['inf']>0) {
                                addWSPMsg('resultmsg', returnIntLang('redirect created pathproperties1')." \"<strong>".trim(fileNamePath(intval($cpath_res['inf']),0,0,1))."</strong>\" ".returnIntLang('redirect created pathproperties2'));
                            }
                        }
                    }
                    $uni[] = array($value['parent']=>$value['path']);
                }
                else {
                    addWSPMsg('errormsg', returnIntLang('redirect double entry pathproperties1')." \"<strong>".trim($value['path'])."</strong>\" ".returnIntLang('redirect double entry pathproperties2'));
                }
            }
        }
        $edit_path = true;
    }
    if ($_POST['save_data']=="removepath") {
        // udpate menu table
        $path = fileNamePath(intval($_POST['removeid']),0,0,1);
        $delpath_sql = 'UPDATE `menu` SET `trash` = 1 WHERE `mid` = '.intval($_POST['removeid']);
        $delpath_res = doSQL($delpath_sql);
        if ($delpath_res['aff']==1) {
            addWSPMsg('noticemsg', returnIntLang('redirect removed pathproperties1')." \"<strong>".$path."</strong>\" ".returnIntLang('redirect removed pathproperties2'));
        }
        $edit_path = true;
    }
}


// head of file - first regular output -------
require("./data/include/header.inc.php");
require("./data/include/navbar.inc.php");
require("./data/include/sidebar.inc.php");

$sitedata = getWSPProperties();

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('redirect headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('redirect info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('redirect mobile legend'); ?></h3>
                            <?php panelOpener(true, array(), $edit_mobile); ?>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="mobileprefs">
				            <p><?php echo returnIntLang('redirect mobile info'); ?></p>
				            <div class="row">
                                <div class="col-md-6">
                                    <div class="fancy-checkbox custom-bgcolor-blue">
                                        <label>
                                            <input type="hidden" name="mobile_pages" value="0" /><input type="checkbox" name="mobile_pages" value="1" <?php if(isset($sitedata['mobile_pages']) && intval($sitedata['mobile_pages'])==1) echo "checked=\"checked\""; ?> />
                                            <span><?php echo returnIntLang('redirect mobile allow mobile'); ?></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="text" name="mobile_path" value="<?php if(isset($sitedata['mobile_path']) && trim($sitedata['mobile_path'])!="") echo $sitedata['mobile_path']; ?>" placeholder="<?php echo returnIntLang('redirect mobile path prefix', false); ?>" class="form-control" />
                                    </div>
                                </div>
                            </div>
                            <p><a href="#" onclick="document.getElementById('mobileprefs').submit(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a><input name="save_data" type="hidden" value="mobile" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('redirect uri based legend'); ?></h3>
                            <?php panelOpener(true, array(), $edit_url); ?>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="urlprefs">
                                <p><?php echo returnIntLang('redirect url based info'); ?></p>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?php echo returnIntLang('str uri'); ?></th>
                                            <th><?php echo returnIntLang('redirect uri rewriting'); ?></th>
                                            <th><?php echo returnIntLang('redirect uri redirect target'); ?></th>
                                            <th><?php echo returnIntLang('str action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        
                                        $ufk=0;
                                        if(isset($sitedata['url_forward_0']) && $sitedata['url_forward_0']!=""):
                                            $forwarddata = array();
                                            foreach($sitedata AS $sitekey => $sitevalue):
                                                if (substr($sitekey,0,12)=="url_forward_"):
                                                    $forwarddata[] = unserialize($sitevalue);
                                                endif;
                                            endforeach;
                                        
                                            for($ufk=0; $ufk<count($forwarddata); $ufk++): ?>
                                                <tr>
                                                    <td><input type="text" id="url_forward_<?php echo intval($ufk); ?>" name="url_forward_<?php echo intval($ufk); ?>[url]" value="<?php echo $forwarddata[$ufk]['url'] ?>" class="form-control"></td>
                                                    <td><input type="hidden" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" value="0" /><input type="checkbox" value="1" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" <?php if(intval($forwarddata[$ufk]['rewrite'])==1): echo "checked=\"checked\""; endif; ?> /></td>
                                                    <td><select class="form-control" name="url_forward_<?php echo intval($ufk); ?>[target]">
                                                        <option value="0"><?php echo returnIntLang('hint choose'); ?></option>
                                                        <?php echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(intval($forwarddata[$ufk]['target'])), 'option', 2); ?>
                                                    </select></td>
                                                    <td><a href="#" onclick="document.getElementById('url_forward_<?php echo intval($ufk); ?>').value=''; document.getElementById('urlprefs').submit(); return false;"><span class="bubblemessage red"><?php echo returnIntLang('str delete', false); ?></span></a></td>
                                                </tr>
                                            <?php endfor; ?>
                                        <?php endif; ?>
                                        <tr>
                                            <td><input type="text" class="form-control" name="url_forward_<?php echo intval($ufk); ?>[url]" placeholder="<?php echo prepareTextField(returnIntLang('redirect new uri', false)); ?>" /></td>
                                            <td><input type="hidden" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" value="0" /><input type="checkbox" value="1" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" /></td>
                                            <td><select class="form-control" name="url_forward_<?php echo intval($ufk); ?>[target]">
                                                <option value="0"><?php echo returnIntLang('hint choose'); ?></option>
                                                <?php echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(), 'option', 2); ?>
                                            </select></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <input name="save_data" type="hidden" value="url" />
                            </form>
                            <p><a href="#" onclick="document.getElementById('urlprefs').submit(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('redirect path based legend'); ?></h3>
                            <?php panelOpener(true, array(), $edit_path); ?>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="pathprefs">
                                <p><?php echo returnIntLang('redirect path based info'); ?></p>
                                <?php
                                
                                $forwarddata = array();
                                // get entries from menu database
                                $forwardmid_sql = 'SELECT `mid`, `connected` AS parent, `filename` AS path, `internlink_id` AS target FROM `menu` WHERE `internlink_id` > 0 AND `trash` = 0 AND `visibility` = 0 ORDER BY `filename`';
                                $forwardmid_res = doSQL($forwardmid_sql);
                                if ($forwardmid_res['num']>0) {
                                    $forwarddata = $forwardmid_res['set'];
                                }
                                
                                ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?php echo returnIntLang('redirect parent path'); ?></th>
                                            <th><?php echo returnIntLang('redirect pathname'); ?></th>
                                            <th><?php echo returnIntLang('redirect path redirect target'); ?></th>
                                            <th><?php echo returnIntLang('str action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        
                                        $tmpsdm = (isset($_SESSION['wspvars']['sdm'])?intval($_SESSION['wspvars']['sdm']):0);
                                        
                                        $fdk = 0;
                                        foreach ($forwarddata AS $fdk => $fdv) {
                                            echo '<tr>';
                                            $_SESSION['wspvars']['sdm'] = 4;
                                            echo '<td><select class="form-control" name="redirect_path['.intval($fdk).'][parent]">';
                                            echo '<option value="0">/</option>';
                                            echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(intval($fdv['parent'])), 'option', 2);
                                            echo '</select></td>';
                                            echo '<td><input type="text" class="form-control" name="redirect_path['.intval($fdk).'][path]" placeholder="'.prepareTextField(returnIntLang('redirect new path', false)).'" value="'.prepareTextField($fdv['path']).'" /></td>';
                                            $_SESSION['wspvars']['sdm'] = 0;
                                            echo '<td><select class="form-control" name="redirect_path['.intval($fdk).'][target]">';
                                            echo '<option value="0">'.returnIntLang('hint choose').'</option>';
                                            echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(intval($fdv['target'])), 'option', 2);
                                            echo '</select></td>';
                                            echo '<td><input type="hidden" name="redirect_path['.intval($fdk).'][mid]" value="'.intval($fdv['mid']).'" /><a onclick="$(\'#pathremoveid\').val('.intval($fdv['mid']).'); $(\'#pathremove\').submit();"><i class="far fa-trash fa-btn fa-btn-form-control"></i></a></td>';
                                            echo '</tr>';
                                        }
                                        $fdk++;
                                        $_SESSION['wspvars']['sdm'] = 4;
                                        
                                        ?>
                                        <tr>
                                            <td><select class="form-control" name="redirect_path[<?php echo intval($fdk); ?>][parent]">
                                                <option value="0">/</option>
                                                <?php echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(), 'option', 2); ?>
                                            </select></td>
                                            <td><input type="text" class="form-control" name="redirect_path[<?php echo intval($fdk); ?>][path]" placeholder="<?php echo prepareTextField(returnIntLang('redirect new path', false)); ?>" /></td>
                                            <?php $_SESSION['wspvars']['sdm'] = 0; ?>
                                            <td><select class="form-control" name="redirect_path[<?php echo intval($fdk); ?>][target]">
                                                <option value="0"><?php echo returnIntLang('hint choose'); ?></option>
                                                <?php echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(), 'option', 2); ?>
                                            </select></td>
                                            <td><input type="hidden" name="redirect_path[<?php echo intval($fdk); ?>][mid]" value="0" /></td>
                                        </tr>
                                        <?php
                                        
                                        $_SESSION['wspvars']['sdm'] = $tmpsdm;
                                        
                                        ?>
                                    </tbody>
                                </table>
                                <input name="save_data" type="hidden" value="path" />
                            </form>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="pathremove">
                                <input type="hidden" name="removeid" id="pathremoveid" value="0" />
                                <input type="hidden" name="save_data" value="removepath" />
                            </form>
                            <p><a href="#" onclick="document.getElementById('pathprefs').submit(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
			<div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('redirect var based legend'); ?></h3>
                            <?php panelOpener(true, array(), $edit_var); ?>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="varprefs">
				                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?php echo returnIntLang('str varname'); ?></th>
                                            <th><?php echo returnIntLang('str varvalue'); ?></th>
                                            <th><?php echo returnIntLang('redirect target'); ?></th>
                                            <th><?php echo returnIntLang('str action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 

                                        $vfk = 0;
                                        if(isset($sitedata['var_forward_0']) && $sitedata['var_forward_0']!=""):
                                            $forwarddata = array();
                                            foreach($sitedata AS $sitekey => $sitevalue):
                                                if (substr($sitekey,0,12)=="var_forward_"):
                                                    $forwarddata[] = unserialize($sitevalue);
                                                endif;
                                            endforeach; 

                                            for($vfk=0; $vfk<count($forwarddata); $vfk++): ?>
                                                <tr>
                                                    <td><input class="form-control" type="text" id="var_forward_<?php echo intval($vfk); ?>" name="var_forward_<?php echo intval($vfk); ?>[varname]" value="<?php echo $forwarddata[$vfk]['varname'] ?>"></td>
                                                    <td><input class="form-control" type="text" name="var_forward_<?php echo intval($vfk); ?>[varvalue]" value="<?php echo $forwarddata[$vfk]['varvalue'] ?>"></td>
                                                    <td><select class="form-control" name="var_forward_<?php echo intval($vfk); ?>[target]">
                                                        <option value="0"><?php echo returnIntLang('hint choose'); ?></option>
                                                        <?php echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(intval($forwarddata[$vfk]['target'])), 'option', 2); ?>
                                                    </select></td>
                                                    <td nowrap="nowrap"><a href="#" onclick="document.getElementById('var_forward_<?php echo intval($vfk); ?>').value=''; document.getElementById('varprefs').submit(); return false;"><?php echo returnIntLang('str delete', false); ?></a></td>
                                                </tr>
                                            <?php endfor; ?>
                                        <?php endif; ?>
					
                                        <tr>
                                            <td><input type="text" name="var_forward_<?php echo intval($vfk); ?>[varname]" placeholder="<?php echo prepareTextField(returnIntLang('redirect new varname', false)); ?>" class="form-control" /></td>
                                            <td><input type="text" name="var_forward_<?php echo intval($vfk); ?>[varvalue]" placeholder="<?php echo prepareTextField(returnIntLang('str varvalue', false)); ?>" class="form-control" /></td>
                                            <td><select class="form-control" name="var_forward_<?php echo intval($vfk); ?>[target]">
                                                <option value="0"><?php echo returnIntLang('hint choose'); ?></option>
                                                <?php echo returnStructureShow('menu', returnStructureArray(), true, 9999, array(), 'option', 2); ?>
                                            </select></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <input name="save_data" type="hidden" value="var" />
                            </form>
                            <p><a href="#" onclick="document.getElementById('varprefs').submit(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                    
<?php include ("./data/include/footer.inc.php"); ?>