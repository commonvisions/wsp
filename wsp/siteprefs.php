<?php
/**
 * global site-setup
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-04-01
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require("./data/include/usestat.inc.php");
require("./data/include/globalvars.inc.php");
// define page params ------------------------
$_SESSION['wspvars']['lockstat'] = 'siteprops';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-wrench',returnIntLang('menu siteprefs'),returnIntLang('menu siteprefs generell'));
$_SESSION['wspvars']['menuposition'] = 'siteprops';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['mpos'] = 1;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
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

/* define page specific functions ------------ */
if (isset($_POST['save_data'])):
	// clean language field
    foreach ($_POST['languages']['longname'] AS $lk => $lv) {
        if (trim($lv)=='') {
            unset($_POST['languages']['longname'][$lk]);
            unset($_POST['languages']['shortcut'][$lk]);
        }
        else {
            $_POST['languages']['shortcut'][$lk] = strtolower($_POST['languages']['shortcut'][$lk]);
        }
    }
    foreach ($_POST AS $key => $value):
		if ($key!="save_data"):
			$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".$key."'";
			doSQL($deletedata_sql);
			if (is_array($value)):
                $insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".$key."', `varvalue` = '".serialize($value)."'";
                doSQL($insertdata_sql);
			else:
				$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".$key."', `varvalue` = '".$value."'";
				doSQL($insertdata_sql);
			endif;
		endif;
	endforeach;
endif;

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
                <h1 class="page-title"><?php echo returnIntLang('generell headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('generell info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmprefs" style="margin: 0px;">
            <div class="row">
                <div class="col-md-6">
                    <!-- SESSION, CODING, TYPE -->
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('generell coding type'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <div class="form-horizontal">
                                <p><?php echo returnIntLang('generell doctype'); ?></p>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <select name="doctype" class="form-control singleselect fullwidth">
                                            <option value="html4trans">HTML 4 Transitional</option>
                                            <option value="html4strict" <?php if($sitedata['doctype']=="html4strict") echo "selected=\"selected\"";?>>HTML 4 Strict</option>
                                            <option value="html5" <?php if($sitedata['doctype']=="html5") echo "selected=\"selected\"";?>>HTML 5 (Strict)</option>
                                            <option value="xhtml1trans" <?php if($sitedata['doctype']=="xhtml1trans") echo "selected=\"selected\"";?>>XHTML 1 Transitional</option>
                                            <option value="xhtml1strict" <?php if($sitedata['doctype']=="xhtml1strict") echo "selected=\"selected\"";?>>XHTML 1 Strict</option>
                                            <option value="xhtml1-1" <?php if($sitedata['doctype']=="xhtml1-1") echo "selected=\"selected\"";?>>XHTML 1.1 (Strict)</option>
                                        </select>
                                    </div>
                                </div>
                                <p><?php echo returnIntLang('generell code'); ?></p>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <select name="codepage" class="form-control singleselect fullwidth">
                                            <option value="utf-8" <?php if($sitedata['codepage']!="iso-8859-1") echo "selected=\"selected\"";?>>UTF-8</option>
                                            <option value="iso-8859-1">ISO-8859-1</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END SESSION, CODING, TYPE -->
                </div>
                <div class="col-md-6">
                    <!-- PLACEHOLDER SETTINGS -->
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('generell contentvars'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <div class="form-horizontal">
                                <?php $show = 4; if(array_key_exists('contentvardesc', $sitedata)): $sitedata['contentvardesc'] = unserializeBroken($sitedata['contentvardesc']); foreach($sitedata['contentvardesc'] AS $sk => $sv): if (trim($sv)==""): unset($sitedata['contentvardesc'][$sk]); endif; endforeach; $show = $show - count($sitedata['contentvardesc']); if($show<2): $show = 2; endif; endif; ?>
                                <?php for($s=0; $s<(count($sitedata['contentvardesc'])+$show); $s++): ?>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <input type="text" name="contentvardesc[]" value="<?php if(isset($sitedata['contentvardesc'][$s])): echo prepareTextField($sitedata['contentvardesc'][$s]); endif; ?>" class="form-control" placeholder="<?php echo returnIntLang('templates contentelement', false)." ".($s+1); ?>" />
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <!-- END PLACEHOLDER SETTINGS -->
                </div>
            </div> 
			
            <?php
			
			$tempdata = unserializeBroken($sitedata['languages']);
			unset($sitedata['languages']);
		
			if (array_key_exists('languages', $tempdata) && is_array($tempdata['languages']['longname'])):
				$sitedata['languages'] = $tempdata['languages'];
			else:
				$sitedata['languages'] = $tempdata;
			endif;
		
			if (!(is_array($sitedata['languages']))):
				$sitedata['languages']['longname'] = array('Deutsch');
				$sitedata['languages']['shortcut'] = array('de');
			endif;
			if (count($sitedata['languages'])==0):
				$sitedata['languages']['longname'] = array('Deutsch');
				$sitedata['languages']['shortcut'] = array('de');
			endif;
		
			?>
			<script language="JavaScript" type="text/javascript">
			<!--
		
			function removeLang(id) {
				$('#' + id).hide('fade', 1000, function() { $(this).remove() } );
				}
			
			// -->
			</script>
			
            <div class="row">
                <div class="col-md-9">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('generell language'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <table class="table table-mobile">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo returnIntLang('str language'); ?></th>
                                        <th><?php echo returnIntLang('str shortcut'); ?></th>
                                        <th><?php echo returnIntLang('str icon'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($sitedata['languages']['longname'] AS $key => $value):
                                        if (trim($sitedata['languages']['longname'][$key])!='' && trim($sitedata['languages']['shortcut'][$key])!=''):
                                        ?>
                                        <tr id='lang_<?php echo $key; ?>'>
                                            <td><?php echo ($key+1); ?></td>
                                            <td><input type="text" name="languages[longname][]" class="form-control" value="<?php echo $sitedata['languages']['longname'][$key]; ?>" /></td>
                                            <td><input type="text" name="languages[shortcut][]" class="form-control" value="<?php echo $sitedata['languages']['shortcut'][$key]; ?>" readonly="readonly" /></td>
                                            <td><?php

                                            if (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".png")):
                                                echo "<img src=\"/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".png\" />";
                                            elseif (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".gif")):
                                                echo "<img src=\"/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".gif\" />";
                                            elseif (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".jpg")):
                                                echo "<img src=\"/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".jpg\" />";
                                            else:
                                                echo "<em>".returnIntLang('generell language noicon')."</em>";
                                            endif;

                                            ?></td>
                                            <td class="text-right"><i class="fa fa-minus-circle" onclick="removeLang('lang_<?php echo $key; ?>')"></i></td>
                                        </tr>
                                        <?php endif; 
                                    endforeach; ?>
                                    <tr>
                                        <td></td>
                                        <td><input type="text" class="form-control" name="languages[longname][]" value="" placeholder="<?php echo returnIntLang('generell language new longname', false); ?>" /></td>
                                        <td><input type="text" name="languages[shortcut][]" class="form-control" value="" data-parsley-length="[2,3]" placeholder="<?php echo returnIntLang('generell language new shortcut', false); ?>" /></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('generell language placement'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <div class="form-horizontal">
                                <p><?php echo returnIntLang('generell language showmode'); ?></p>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <select name="showlang" class="form-control singleselect fullwidth">
                                            <option value="text" <?php if($sitedata['showlang']!="icon") echo "selected=\"selected\""; ?>><?php echo returnIntLang('generell language textlist'); ?></option>
                                            <option value="icon" <?php if($sitedata['showlang']=="icon") echo "selected=\"selected\""; ?>><?php echo returnIntLang('str icon'); ?></option>
                                            <option value="dropdown" <?php if($sitedata['showlang']=="dropdown") echo "selected=\"selected\""; ?>><?php echo returnIntLang('str dropdown'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <p><?php echo returnIntLang('generell language alternative'); ?></p>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <select name="setoutputlang" class="form-control singleselect fullwidth">
                                            <option value="page" <?php if($sitedata['setoutputlang']!="content") echo "selected=\"selected\""; ?>><?php echo returnIntLang('generell language pageitem'); ?></option>
                                            <option value="content" <?php if($sitedata['setoutputlang']=="content") echo "selected=\"selected\""; ?>><?php echo returnIntLang('generell language contentitem'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p><input type="button" onclick="document.getElementById('frmprefs').submit(); return false;" class="btn btn-primary" value="<?php echo returnIntLang('str save', false); ?>" /><input name="save_data" type="hidden" value="Speichern" /></p>
            </form>
        </div>
    </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script>

    $(document).ready(function() { 
        $('.singleselect').multiselect();
    });
    
</script>

<?php require ("./data/include/footer.inc.php"); ?>