<?php
/**
 * search engine related properties
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-10-30
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-wrench',returnIntLang('menu siteprefs'),returnIntLang('menu siteprefs seo'));
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['mpos'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'dropify.css',
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'dropify.js',
    'jquery/jquery.autogrowtextarea.js',
    'bootstrap/bootstrap-multiselect.js'
    );
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

@include ("./data/include/config.inc.php");

if (isset($_POST['savedata'])) {
    foreach ($_POST AS $key => $value) {
		if ($key!="savedata" && $key!='removeset') {
			$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL($key)."'";
			doSQL($deletedata_sql);
            if (is_array($value)) {
				$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL(serialize($value))."'";
                $insertdata_res = doSQL($insertdata_sql);
            } else if (trim($value)!='') {
                $insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL($value)."'";
                $insertdata_res = doSQL($insertdata_sql);
			}
        } else if ($key=='removeset') {
            if (intval($value['favicon'])==1) { 
                deleteFile("/media/screen/favicon.ico");
            }
            if (intval($value['smartphone'])==1) {
                deleteFile("/media/screen/iphone_favicon.png");
            }
            if (intval($value['opengraph'])==1) {
                deleteFile("/media/screen/ogscreenshot.png");
            }
        }
	}
    foreach ($_FILES AS $key => $value) {
        if ($key=='favicon' && $value['name']!='' && intval($value['error'])==0 && intval($value['size'])>0) {
            $docopy = copyFile($value['tmp_name'], "/media/screen/favicon.ico");
            if ($docopy===true) {
                addWSPMsg('noticemsg', 'seo favicon uploaded');
            } else {
                addWSPMsg('errormsg', 'seo favicon could not be copied to final location');
            }
        }
        if ($key=='smartphone' && $value['name']!='' && intval($value['error'])==0 && intval($value['size'])>0) {
            $docopy = copyFile($value['tmp_name'], "/media/screen/iphone_favicon.png");
            if ($docopy===true) {
                addWSPMsg('noticemsg', 'seo smartphone uploaded');
            } else {
                addWSPMsg('errormsg', 'seo smartphone could not be copied to final location');
            }
        }
        if ($key=='opengraph' && $value['name']!='' && intval($value['error'])==0 && intval($value['size'])>0) {
            $docopy = copyFile($value['tmp_name'], "/media/screen/ogscreenshot.png");
            if ($docopy===true) {
                addWSPMsg('noticemsg', 'seo opengraph uploaded');
            } else {
                addWSPMsg('errormsg', 'seo opengraph could not be copied to final location');
            }
        }
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
                <h1 class="page-title"><?php echo returnIntLang('seo headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('seo info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmprefs">
            <div class="container-fluid">
                <?php showWSPMsg(1); ?>
                <div class="row">
                    <div class="col-md-9">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('seo legend'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-2"><?php echo returnIntLang('seo title'); ?></div>
                                    <div class="col-md-10">
                                        <div class="input-group form-group">
                                            <input class="form-control" name="sitetitle" id="sitetitle" type="text" value="<?php if(isset($sitedata['sitetitle'])) echo prepareTextField($sitedata['sitetitle']); ?>" onkeyup="sQ('sitetitle',80,200);" />
                                            <span id="show_sitetitle_length" class="input-group-addon"><?php if(isset($sitedata['sitetitle'])) echo strlen(prepareTextField($sitedata['sitetitle'])); ?>/200</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10 col-md-offset-2">
                                        <p><?php echo returnIntLang('seo user var in title1'); ?> [%PAGENAME%] <?php echo returnIntLang('seo user var in title2'); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2"><?php echo returnIntLang('str shortdesc'); ?></div>
                                    <div class="col-md-10">
                                        <div class="input-group form-group">
                                            <textarea name="sitedesc" id="sitedesc" class="form-control noresize autogrow" onkeyup="sQ('sitedesc',150,300);"><?php if(isset($sitedata['sitedesc'])) echo prepareTextField(stripslashes($sitedata['sitedesc'])); ?></textarea>
                                            <span id="show_sitedesc_length" class="input-group-addon"><?php if(isset($sitedata['sitedesc'])) echo strlen(prepareTextField($sitedata['sitedesc'])); ?>/300</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2"><?php echo returnIntLang('seo keywords'); ?></div>
                                    <div class="col-md-10">
                                        <div class="input-group form-group">
                                            <textarea name="sitekeys" id="sitekeys" cols="20" rows="7" class="form-control noresize autogrow" onkeyup="sQ('sitekeys',300,1000);"><?php if(isset($sitedata['sitedesc'])) echo prepareTextField(stripslashes($sitedata['sitekeys'])); ?></textarea>
                                            <span id="show_sitekeys_length" class="input-group-addon"><?php if(isset($sitedata['sitedesc'])) echo strlen(prepareTextField($sitedata['sitekeys'])); ?>/1000</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <?php require('./data/panels/seoindex.inc.php'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('icon favicon'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <input name="favicon" type="file" id="dropify-favicon" data-allowed-file-extensions="ico png" data-default-file="<?php if (is_file(DOCUMENT_ROOT."/media/screen/favicon.ico")): echo "/media/screen/favicon.ico"; endif; ?>"><input type="hidden" name="removeset[favicon]" value="0" id="removeset-favicon" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('icon smartphone'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <input name="smartphone" <?php if (is_file(DOCUMENT_ROOT."/media/screen/iphone_favicon.png")): echo " value='set' "; endif; ?> type="file" id="dropify-iphone" data-allowed-file-extensions="png" data-default-file="<?php if (is_file(DOCUMENT_ROOT."/media/screen/iphone_favicon.png")): echo "/media/screen/iphone_favicon.png"; endif; ?>"><input type="hidden" name="removeset[smartphone]" value="0" id="removeset-smartphone" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('file opengraph'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <input name="opengraph" type="file" id="dropify-opengraph" data-allowed-file-extensions="png" data-default-file="<?php if (is_file(DOCUMENT_ROOT."/media/screen/ogscreenshot.png")): echo "/media/screen/ogscreenshot.png"; endif; ?>"><input type="hidden" name="removeset[opengraph]" value="0" id="removeset-opengraph" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('googlepref headline'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <p><?php echo returnIntLang('googlepref verifyid'); ?></p>
                                    <p><input name="googleverify" type="text" class="form-control" value="<?php if(isset($sitedata['googleverify'])) echo prepareTextField($sitedata['googleverify']); ?>" /></p>
                                    <p><?php echo returnIntLang('googlepref analyticsid'); ?></p>
                                    <p><input name="analyticsid" type="text" class="form-control" value="<?php if(isset($sitedata['analyticsid'])) echo prepareTextField($sitedata['analyticsid']); ?>" /></p>
                                    <p><i><?php echo returnIntLang('googlepref analyticsid info'); ?></i></p>
                                    <p><?php echo returnIntLang('googlepref analytics'); ?></p>
                                    <p><textarea name="googleanalytics" id="googleanalytics" class="form-control autogrow noresize" rows="5"><?php if(isset($sitedata['googleanalytics'])) echo $sitedata['googleanalytics']; ?></textarea></p>
                                    <p><i><?php echo returnIntLang('googlepref analytics info'); ?></i></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('generell meta'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <p><?php echo returnIntLang('generell meta baseurl'); ?></p>
                                    <p><div class="input-group">
                                        <span class="input-group-addon">http(s)://</span>
                                        <input class="form-control" name="siteurl" type="text" value="<?php if (isset($sitedata['siteurl'])) echo prepareTextField($sitedata['siteurl']); ?>" placeholder="<?php echo returnIntLang('generell meta baseurl url without http(s)://', false); ?>" />
                                    </div></p>
                                    <p><?php echo returnIntLang('generell meta author'); ?></p>
                                    <p><input class="form-control" name="siteauthor" type="text" value="<?php if (isset($sitedata['siteauthor'])) echo prepareTextField($sitedata['siteauthor']); ?>" placeholder="<?php echo returnIntLang('generell meta author help', false); ?>" /></p>
                                    <p><?php echo returnIntLang('generell meta copy url'); ?></p>
                                    <div class="input-group">
                                        <span class="input-group-addon">http(s)://</span>
                                        <input class="form-control" name="sitecopy" type="text" value="<?php if (isset($sitedata['sitecopy'])) echo prepareTextField($sitedata['sitecopy']); ?>" placeholder="<?php echo returnIntLang('generell meta copy url without http://', false); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p><input type="button" onclick="document.getElementById('frmprefs').submit(); return false;" class="btn btn-primary" value="<?php echo returnIntLang('str save'); ?>" /></p>
                <input type="hidden" name="savedata" value="true" />
            </div>
        </form>
    </div>
</div>

<script>

$(document).ready(function() {
    
    $('#siterobots').multiselect({ maxHeight: 300 });
    $('.autogrow').autoGrow();
    $('.dropify').dropify();
    
    var drFav = $('#dropify-favicon').dropify({messages: { default: '<?php echo returnIntLang('seo upload ico or png file', false); ?>' }});
    drFav.on('dropify.beforeClear', function(event, element) {
        return confirm("<?php echo returnIntLang('seo really delete file', false); ?> \"" + element.file.name + "\" ?");
        });
    drFav.on('dropify.afterClear', function(event, element) {
        alert('<?php echo returnIntLang('seo file will be deleted when saving', false); ?>');
        $('#removeset-favicon').val(1);
        });
    
    var drIphone = $('#dropify-iphone').dropify({messages: { default: '<?php echo returnIntLang('seo upload png file', false); ?>' }});
    drIphone.on('dropify.beforeClear', function(event, element) {
        return confirm("<?php echo returnIntLang('seo really delete file', false); ?> \"" + element.file.name + "\" ?");
        });
    drIphone.on('dropify.afterClear', function(event, element) {
        alert('<?php echo returnIntLang('seo file will be deleted when saving', false); ?>');
        $('#removeset-smartphone').val(1);
        });
    
    var drOGMedia = $('#dropify-opengraph').dropify({messages: { default: '<?php echo returnIntLang('seo upload png file', false); ?>' }});
    drOGMedia.on('dropify.beforeClear', function(event, element) {
        return confirm("<?php echo returnIntLang('seo really delete file', false); ?> \"" + element.file.name + "\" ?");
        });
    drOGMedia.on('dropify.afterClear', function(event, element) {
        alert('<?php echo returnIntLang('seo file will be deleted when saving', false); ?>');
        $('#removeset-opengraph').val(1);
        });
    
    sQ('sitetitle',80,200);
    sQ('sitedesc',150,300);
    sQ('sitekeys',300,1000);
    
});
</script>

<?php include ("./data/include/footer.inc.php"); ?>