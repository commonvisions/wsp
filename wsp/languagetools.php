<?php
/**
 * @description language tools
 * @author stefan@covi.de
 * @since 4.0
 * @version 7.0
 * @lastchange 2019-11-04
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'langtools';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content localize'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js'
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$emptylang = false;
/* define page specific funcs ----------------- */

if (isset($_POST['transformlang']) && in_array(trim($_POST['transformlang']), $_SESSION['wspvars']['sitelanguages']['shortcut']) && trim($_POST['transformlang'])!="de") {
	$break = false;
	if (isset($_POST['replacecontents']) && $_POST['replacecontents']==1):
		if (trim($_POST['adminpass'])==""):
			addWSPMsg("errormsg", "<p>".returnIntLang('localisation confirm with admin pass', true)."</p>");
			$break = true;
		else:
			$admincheck_sql = "SELECT `rid` FROM `restrictions` WHERE `rid` = ".$_SESSION['wspvars']['userid']." AND `usertype` = 'admin' AND `pass` = '".md5(trim($_POST['adminpass']))."'";
			$admincheck_res = mysql_query($admincheck_sql);
			if ($admincheck_res):
				$admincheck_num = mysql_num_rows($admincheck_res);
			endif;
			if ($admincheck_num>0):
				$sql = "UPDATE `content` SET `visibility` = 0, `trash` = 1 WHERE `content_lang` = '".$_POST['transformlang']."'";
				if (mysql_query($sql)):
					addWSPMsg("noticemsg", "<p>Die Inhalte [".$_POST['transformlang']."] wurden gel&ouml;scht.</p>");
				else:
					addWSPMsg("errormsg", "<p>".returnIntLang('localisation error overwriting contents', true)."</p>");
					$break = true;
				endif;
			else:
				addWSPMsg("errormsg", "<p>".returnIntLang('localisation confirm with admin pass', true)."</p>");
				$break = true;
			endif;
		endif;
	endif;
	if (!$break):
		if (intval($_POST['contentarea'])==0):
			$content_sql = "SELECT * FROM `content` WHERE `trash` = 0 && `content_lang` = '".$_POST['transformfrom']."' ORDER BY `cid`";
			$content_res = mysql_query($content_sql);
			if ($content_res):
				$content_num = mysql_num_rows($content_res);
			endif;
		elseif (intval($_POST['includesubs'])==0):
			$content_sql = "SELECT * FROM `content` WHERE `trash` = 0 && `mid` = ".intval($_POST['contentarea'])." && `content_lang` = '".$_POST['transformfrom']."' ORDER BY `cid`";
			$content_res = mysql_query($content_sql);
			if ($content_res):
				$content_num = mysql_num_rows($content_res);
			endif;
		else:
			$midarray = returnIDRoot(intval($_POST['contentarea']), array(intval($_POST['contentarea'])));
			$content_sql = "SELECT * FROM `content` WHERE `trash` = 0 && `mid` IN ('".implode("','", $midarray)."') && `content_lang` = '".$_POST['transformfrom']."' ORDER BY `cid`";
			$content_res = mysql_query($content_sql);
			if ($content_res):
				$content_num = mysql_num_rows($content_res);
			endif;
		endif;
		if ($content_num>0):
			$sqlstat = 0;
			for ($cres=0; $cres<$content_num; $cres++):
				$sql = "INSERT INTO `content` (`mid`, `globalcontent_id`, `connected`, `content_area`, `content_lang`, `position`, `visibility`, `showday`, `showtime`, `sid`, `valuefields`, `xajaxfuncnames`, `lastchange`, `interpreter_guid`, `trash`, `container`, `containerclass`, `containeranchor`, `displayclass`) VALUES (".intval(mysql_result($content_res, $cres, 'mid')).", ".intval(mysql_result($content_res, $cres, 'globalcontent_id')).", ".intval(mysql_result($content_res, $cres, 'connected')).", ".intval(mysql_result($content_res, $cres, 'content_area')).", '".trim($_POST['transformlang'])."', ".intval(mysql_result($content_res, $cres, 'position')).", ".intval(mysql_result($content_res, $cres, 'visibility')).", '".mysql_real_escape_string(mysql_result($content_res, $cres, 'showday'))."', '".mysql_real_escape_string(mysql_result($content_res, $cres, 'showtime'))."', ".intval(mysql_result($content_res, $cres, 'sid')).", '".mysql_real_escape_string(mysql_result($content_res, $cres, 'valuefields'))."', '".mysql_real_escape_string(mysql_result($content_res, $cres, 'xajaxfuncnames'))."', '".time()."', '".mysql_result($content_res, $cres, 'interpreter_guid')."', 0, '".mysql_real_escape_string(mysql_result($content_res, $cres, 'container'))."', '".mysql_real_escape_string(mysql_result($content_res, $cres, 'containerclass'))."', '".mysql_real_escape_string(mysql_result($content_res, $cres, 'containeranchor'))."', '".mysql_real_escape_string(mysql_result($content_res, $cres, 'displayclass'))."')";
				if (mysql_query($sql)):
					$sqlstat++;
				else:
					addWSPMsg('errormsg', "<p>".$sql."</p>"."<p>".mysql_error()."</p>");
				endif;
			endfor;
			addWSPMsg("noticemsg", "<p>".$sqlstat." Inhalte [".$_POST['transformfrom']."] wurden in die neue Sprache [".$_POST['transformlang']."] &uuml;bertragen.</p>");
		endif;
	endif;
}
if (isset($_POST['freelang']) && trim($_POST['freelang'])!="de") {
	if (trim($_POST['adminpass'])==""):
		addWSPMsg("errormsg", "<p>".returnIntLang('localisation confirm freelang with admin pass', true)."</p>");
	else:
		$admincheck_sql = "SELECT `rid` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid'])." AND `usertype` = 1 AND `pass` = '".escapeSQL(md5(trim($_POST['adminpass'])))."'";
		$admincheck_res = getNumSQL($admincheck_sql);
		if ($admincheck_res>0):
			$sql = "DELETE FROM `content` WHERE `content_lang` = '".escapeSQL($_POST['freelang'])."'";
			$res = doSQL($sql);
            if ($res['aff']>0):
				addWSPMsg("resultmsg", returnIntLang('localisation freelang removed contents 1').' '.$res['aff'].' '.returnIntLang('localisation freelang removed contents 2'));
            else:
				addWSPMsg("noticemsg", returnIntLang('localisation freelang no contents removed'));
			endif;
		else:
			addWSPMsg("errormsg", returnIntLang('localisation confirm freelang with admin pass'));
		endif;
	endif;
}

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
include ("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('localisation headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('localisation info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php 
            
            if (count($_SESSION['wspvars']['sitelanguages']['shortcut'])>1) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('localisation usecontent', true); ?></h3>
                            </div>
                            <div class="panel-body">
                                <script language="JavaScript1.2" type="text/javascript">
                                <!--

                                function checkforpass() {
                                    if (document.getElementById('replacecontents').checked) {
                                        document.getElementById('adminpass').disabled = false;
                                        }
                                    else {
                                        document.getElementById('adminpass').value = '';
                                        document.getElementById('adminpass').disabled = true;
                                        }
                                    }

                                function langtransform() {
                                    if (document.getElementById('replacecontents').checked) {
                                        if (document.getElementById('adminpass').value!="") {
                                            if (confirm('Wollen Sie sicher die bestehenden lokalisierten Inhalte durch die neuen Inhalte der Hauptsprache [Deutsch] ersetzen?')) {
                                                document.getElementById('translator').submit();
                                                }
                                            }
                                        else {
                                            alert ('<?php echo returnIntLang('localisation confirm with admin pass', false); ?>');
                                            }
                                        }
                                    else {
                                        document.getElementById('translator').submit();
                                        }
                                    }

                                // -->
                                </script>
                                <form name="translator" id="translator" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                    <div class="row">
                                        <div class="col-md-3"><?php echo returnIntLang('localisation copycontent', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select name="transformfrom" class="singleselect"><?php

                                                foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value):
                                                    if ($_SESSION['wspvars']['sitelanguages']['shortcut'][$key]!=''): echo "<option value=\"".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."\">".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</option>"; endif;
                                                endforeach;

                                                ?></select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <?php echo returnIntLang('localisation pastecontent', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select name="transformlang" class="singleselect"><?php
				
                                                foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value):
                                                    if ($key>0 && $_SESSION['wspvars']['sitelanguages']['shortcut'][$key]!=''): 
                                                        echo "<option value=\"".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."\">".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</option>";
                                                    endif;
                                                endforeach;

                                                ?></select>
                                            </div>    
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3"><?php echo returnIntLang('localisation contentarea', true); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <select id="contentarea" name="contentarea" size="1" class="singleselect fullwidth">
                                                    <option value="0"><?php echo returnIntLang('localisation fullpage'); ?></option>
                                                    <?php echo returnStructureItem('menu', 0, true, 9999, array(), 'option'); ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3"><?php echo returnIntLang('localisation includesubs', true); ?> <input type="hidden" name="includesubs" value="0" /><input type="checkbox" name="includesubs" id="includesubs" value="1" /></div>
                                        <div class="col-md-3"><?php echo returnIntLang('localisation overwritecontents', true); ?> <input type="checkbox" name="replacecontents" id="replacecontents" value="1" onchange="checkforpass();"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3"><?php echo returnIntLang('str password', true); ?></div>
                                        <div class="col-md-3"><input name="adminpass" id="adminpass" type="password" disabled="disabled" value="" class="form-control"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="panel-footer">
                                <a href="#" onclick="langtransform();" class="btn btn-primary"><?php echo returnIntLang('str doaction', false); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } 
            else {
                $emptylang = true;
            }

            $difflang = array();
            $findlang_sql = "SELECT DISTINCT `content_lang` FROM `content` WHERE `trash` = 0";
            $contentlang = getResultSQL($findlang_sql);
            if (is_array($contentlang) && count($_SESSION['wspvars']['sitelanguages']['shortcut'])>0) {
                $difflang = array_diff($contentlang, $_SESSION['wspvars']['sitelanguages']['shortcut']);
            }
            
            if (count($difflang)>0) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('localisation freelanguage', true); ?></h3>
                                <p class="panel-subtitle"><?php echo returnIntLang('localisation freelangdesc', true); ?></p>
                            </div>
                            <div class="panel-body">
                                <script language="JavaScript1.2" type="text/javascript">
                                <!--

                                function doFreelang() {
                                    if ($('#free_adminpass').val()!="") {
                                        if (confirm('<?php echo returnIntLang('localisation freelang confirm', true); ?>')) {
                                            document.getElementById('freezer').submit();
                                            }
                                        }
                                    else {
                                        alert ('<?php echo returnIntLang('localisation password freelang helptext', true); ?>');
                                        }
                                    }

                                // -->
                                </script>
                                <form name="freezer" id="freezer" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                    <select id="freelang" name="freelang" class="multiselect multiselect-custom"><?php

                                    foreach ($difflang AS $key => $value):
                                        if ($value!="de"):
                                            echo "<option value=\"".$value."\">".$value."</option>";
                                        endif;
                                    endforeach;

                                    ?></select>
                                    <?php echo returnIntLang('str password', true); ?> <input name="adminpass" id="free_adminpass" type="password" value="">
                                </form>
                            </div>
                            <div class='panel-footer'>
                                <a href="#" onclick="doFreelang();" class="btn btn-primary"><?php echo returnIntLang('str doaction', false); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } 
            else if ($emptylang) {
                
                $loclang = array('de','en','fr','nl','jp','cn');
                $loclang = $loclang[array_rand($loclang)];
                
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <h3 style="text-align: center; font-weight: 300"><?php echo returnIntLang('localisation there is nothin to do '.$loclang, false); ?></h3>
                        <h1 style="text-align: center; font-size: 10vw;">
                            <i class="fas fa-language"></i>
                        </h1>
                    </div>
                </div>
                <?php
            } ?>
            
        </div>
    </div>
</div>

<script>

    $(document).ready(function() { 
        $('.singleselect').multiselect();
    });
    
</script>

<?php include ("./data/include/footer.inc.php"); ?>