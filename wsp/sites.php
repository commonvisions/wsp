<?php
/**
 * global site-setup
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-03-08
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require("./data/include/usestat.inc.php");
require("./data/include/globalvars.inc.php");
// define page params ------------------------
$_SESSION['wspvars']['lockstat'] = 'siteprops';
$_SESSION['wspvars']['menuposition'] = 'sites';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['mpos'] = 2;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array('dropify.css','bootstrap-multiselect.css');
$_SESSION['wspvars']['addpagejs'] = array('bootstrap/bootstrap-multiselect.js');
// second includes ---------------------------
require("./data/include/checkuser.inc.php");
require("./data/include/errorhandler.inc.php");
require("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
$s = 0; // for post and save activity
/* define page specific functions ------------ */
if (isset($_POST['save_data']) && trim($_POST['save_data'])=="sites"):
	if (is_array($_POST['sites']) && count($_POST['sites'])>0):
		for ($sp=0; $sp<=max(array_flip($_POST['sites'])); $sp++):
			$sql = "DELETE FROM `wspproperties` WHERE `varname` LIKE 'sites_".$sp."%'";
			doSQL($sql);
			if (isset($_POST['sites'][$sp]) && trim($_POST['sites'][$sp])!=''):
				$sql = "INSERT INTO `wspproperties` SET `varname` = 'sites_".$sp."', `varvalue` = '".mysql_real_escape_string(trim($_POST['sites'][$sp]))."'";
				doSQL($sql);
			else:
				$sql = "DELETE FROM `wspproperties` WHERE `varname` LIKE 'sites_".$sp."%'";
				doSQL($sql);
			endif;
		endfor;
	endif;
    if (isset($_POST['siteprop']) && is_array($_POST['siteprop']) && count($_POST['siteprop'])>0):
        foreach ($_POST['siteprop'] AS $pk => $pv):
            foreach ($pv AS $pvk => $pvv):
                $sql = "DELETE FROM `wspproperties` WHERE `varname` = 'sites_".intval($pk)."_".trim($pvk)."'";
                doSQL($sql);
                if (isset($_POST['sites'][intval($pk)]) && trim($_POST['sites'][intval($pk)])!=''):
                    $sql = "INSERT INTO `wspproperties` SET `varname` = 'sites_".intval($pk)."_".trim($pvk)."', `varvalue` = '".trim($pvv)."'";
                    doSQL($sql);
                endif;
            endforeach;
        endforeach;
    endif;
endif;

// head of file - first regular output -------
require("./data/include/header.inc.php");
require("./data/include/navbar.inc.php");
require("./data/include/sidebar.inc.php");

$sitedata = getWSPProperties();

$siteinfo_sql = "SELECT * FROM `wspproperties` WHERE `varname` LIKE 'sites_%'";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0):
	for ($sres=0; $sres<$siteinfo_res['num']; $sres++):
		$siteinfo = explode("_", $siteinfo_res['set'][$sres]['varname']);
		if (count($siteinfo)==2):
			$sitedata[($siteinfo[0])][($siteinfo[1])]['name'] = $siteinfo_res['set'][$sres]['varvalue'];
		elseif (count($siteinfo)==3):
			$sitedata[($siteinfo[0])][($siteinfo[1])][($siteinfo[2])] = $siteinfo_res['set'][$sres]['varvalue'];
		endif;
	endfor;
endif;
$sitedata['sites'][] = array('name' => '');

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('sites headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('sites info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php $sk = 0; if(isset($sitedata['sites']) && is_array($sitedata['sites']) && count($sitedata['sites'])>0): foreach($sitedata['sites'] AS $sk => $sv): if(isset($sv['name'])): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('sites overview legend'); ?></h3>
                            <?php panelOpener(true, array(), false); ?>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="sites">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th class="col-md-3"><?php echo returnIntLang('sites name'); ?></th>
                                        <th class="col-md-3"><?php echo returnIntLang('sites entrypoint'); ?></th>
                                        <th class="col-md-2"><?php echo returnIntLang('sites imagefolder'); ?></th>
                                        <th class="col-md-2"><?php echo returnIntLang('sites documentfolder'); ?></th>
                                        <th class="col-md-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    
                                        <tr id='site-<?php echo $sk; ?>'>
                                            <td><?php echo ($sk); ?></td>
                                            <td class="col-md-3"><input type="text" name="sites[<?php echo $sk; ?>]" value="<?php echo $sv['name']; ?>" class="form-control" /></td>
                                            <td class="col-md-3"><select class="multiselect form-control" style="width: 100%;" name="siteprop[<?php echo $sk; ?>][homepage]">
                                                <?php /* getMenuLevel(0, 3, 4, array($sv['homepage'])); */ ?>
                                            </select></td>
                                            <td class="col-md-2"><select class="multiselect-filter form-control" name="siteprop[<?php echo $sk; ?>][images]" style="width: 100%;" >
                                                <option value="0"><?php echo returnIntLang('site prop allmedia'); ?></option>
                                                <?php 

                                                $directory = array();
                                                mediaDirList('/media/images/');
                                                sort($directory);
                                                foreach ($directory AS $k => $v):
                                                    echo "<option value='".$v."' ";
                                                    if (isset($sv['images']) && $v==$sv['images']): echo " selected='selected' "; endif;
                                                    echo ">".str_replace("/media/images/", "/", $v)."</option>";
                                                endforeach;

                                                ?>
                                            </select></td>
                                            <td class="col-md-2"><select class="multiselect-filter" name="siteprop[<?php echo $sk; ?>][download]">
                                                <option value="0"><?php echo returnIntLang('site prop allmedia'); ?></option>
                                                <?php 

                                                $directory = array();
                                                mediaDirList('/media/download/');
                                                sort($directory);
                                                foreach ($directory AS $k => $v):
                                                    echo "<option value='".$v."' ";
                                                    if (isset($sv['download']) && $v==$sv['download']): echo " selected='selected' "; endif;
                                                    echo ">".str_replace("/media/download/", "/", $v)."</option>";
                                                endforeach;

                                                ?>
                                            </select></td>
                                            <td class="col-md-2"><a href="?op=edit&id=<?php echo $sk; ?>"><i class="fa fa-pencil"></i></a> <?php if($sv['name']!=''): ?><a onclick="removeSite(<?php echo $sk; ?>);"><i class="fa fa-trash"></i></a><?php endif; ?></td>
                                        </tr>
                                    
                                </tbody>
                            </table>
                            <p><input type="button" onclick="document.getElementById('sites').submit(); return false;" class="btn btn-primary" value="<?php echo returnIntLang('str save'); ?>" /><input name="save_data" type="hidden" value="sites" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; endforeach; endif; ?>
        </div>
	</div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script type="text/javascript">
<!--
		
function removeSite(siteID) {
	$('#site-'+siteID).remove();
	$('#fieldset_site_-'+siteID).remove();
	}

$(function() {
    
    $('.multiselect').multiselect({ maxHeight: 300 });
    $('.multiselect-filter').multiselect({
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        maxHeight: 300
    });
});

// -->
</script>
    
<?php require ("./data/include/footer.inc.php"); ?>