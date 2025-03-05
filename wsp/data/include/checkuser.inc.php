<?php
/**
 * check login
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0.1
 * @lastchange 2021-09-21
 */

// login-status der aktuellen usevar pruefen
if (array_key_exists('usevar', $_SESSION['wspvars'])) {
	$secure_sql = "SELECT * FROM `".DB_PREFIX."security` WHERE `usevar` = '".$_SESSION['wspvars']['usevar']."'";
	$secure_res = doSQL($secure_sql);
} else {
    $secure_res['num'] = 0;
}

if ($secure_res['num']!=0) {
    $_SESSION['wspvars']['lockscreen'] = false;
    if ($secure_res['set'][0]['timevar']<(time()-(60*30))):
        $_SESSION['wspvars']['lockscreen'] = true;
        $_SESSION['wspvars']['preventleave'] = true;
    endif;
    /* found sessionvar => user is logged in */
	$_SESSION['wspvars']['actusersid'] = $secure_res['set'][0]['sid'];
	$_SESSION['wspvars']['userid'] = $secure_res['set'][0]['userid'];
	$_SESSION['wspvars']['logintime'] = $secure_res['set'][0]['logintime'];
	// detect if someone else has changed the actual page and this page should be locked	
	if (array_key_exists('fposcheck', $_SESSION['wspvars']) && $_SESSION['wspvars']['fposcheck'] && array_key_exists('fpos', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['fpos'])!=''):
		$fposition_num = 0;
		$fposition_sql = "SELECT * FROM `".DB_PREFIX."security` WHERE `position` = '".escapeSQL($_SESSION['wspvars']['fpos'])."' AND `usevar` != '".escapeSQL($_SESSION['wspvars']['usevar'])."'";
		$fposition_res = doSQL($fposition_sql);
		if ($fposition_res['num']>0):
			$fileusage_userid = $fposition_res['set'][0]['userid'];
			$fileusage_usevar = $fposition_res['set'][0]['usevar'];
		endif;
	endif;
	$_SESSION['wspvars']['related'] = cleanPath(str_replace(WSP_DIR,'',$_SERVER['PHP_SELF']));
    $indexlogin = true;
}
else {
    $_SESSION['wspvars']['related'] = cleanPath(str_replace(WSP_DIR,'',$_SERVER['PHP_SELF']));
    header("location: ".str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/login.php")));
	die();
}

$rights_res = array('num' => 0);
if (isset($_SESSION['wspvars']['userid'])) {
	$rights_sql = "SELECT * FROM `".DB_PREFIX."restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
	$rights_res = doSQL($rights_sql);
	$rights_num = $rights_res['num'];
}

if ($rights_res['num']==1) {
	$_SESSION['wspvars']['usertype'] = $rights_res['set'][0]['usertype'];
	$_SESSION['wspvars']['realname'] = $rights_res['set'][0]['realname'];
	$_SESSION['wspvars']['messages'] = $rights_res['set'][0]['usernotice'];
	$_SESSION['wspvars']['disablenews'] = intval($rights_res['set'][0]['disablenews']); // added 2015-01-23
	$_SESSION['wspvars']['saveprops'] = intval($rights_res['set'][0]['saveprops']); // added 2015-01-23
    // prepare emtpy rights array
    $_SESSION['wspvars']['rights'] = array();
    $tmp_rights = unserializeBroken($rights_res['set'][0]['rights']);
    if (is_array($tmp_rights) && count($tmp_rights)>0) {
        $_SESSION['wspvars']['rights'] = $tmp_rights;
	}
    // prepare empty modrights array
	if (!(isset($_SESSION['wspvars']['modrights']))) {
		$_SESSION['wspvars']['modrights'] = array();
	}
	// festlegung der allgemeinen rechte
	$temp_menuid_cols = unserializeBroken($rights_res['set'][0]['idrights']);

	if (is_array($temp_menuid_cols)) {
		foreach ($temp_menuid_cols AS $key => $value) {
			$_SESSION['wspvars']['rights'][$key] = 2;
			$_SESSION['wspvars']['rights'][$key."_id"] = implode(",", $value);
			$_SESSION['wspvars']['rights'][$key."_array"] = $value;
		}
		foreach (unserializeBroken($rights_res['set'][0]['rights']) as $key => $value) {
			$temp_menuid_rights[$key] = array();
			if (strlen($key)!=36) {
				$_SESSION['wspvars']['rights'][$key] = $value;
				if ($value>1) {
					$_SESSION['wspvars']['rights'][$key."_id"] = $temp_menuid_rights[$key];
				}
			} else {
				$_SESSION['wspvars']['modrights'][$key] = $value;
			}
		}
	} else {
		$temp_menuid_cols = @explode("\n", $rights_res['set'][0]['idrights']);
		if (is_array($temp_menuid_cols)) {
			for ($m=0;$m<count($temp_menuid_cols);$m++) {
				$temp_menuid_elements = @explode(":", $temp_menuid_cols[$m]);
				if (is_array($temp_menuid_elements)) {
					if (key_exists(1, $temp_menuid_elements)) {
						$temp_menuid_rights[$temp_menuid_elements[0]] = $temp_menuid_elements[1];
					}
				}
			}
		}
		if (isset($rights_res['set'][0]['rights']) && is_array($rights_res['set'][0]['rights'])) {
            foreach (unserializeBroken($rights_res['set'][0]['rights']) as $key => $value) {
                if (strlen($key)!=36) {
                    $_SESSION['wspvars']['rights'][$key] = $value;
                    if ($value>1) {
                        $_SESSION['wspvars']['rights'][$key."_id"] = $temp_menuid_rights[$key];
					}
				} else {
                    $_SESSION['wspvars']['modrights'][$key] = $value;
                }
            }
        }
	}
	//
	// festlegung modularer rechte
	//
	$modrights_sql = "SELECT `guid`, `options` FROM `wsprights`";
	$modrights_res = doSQL($modrights_sql);
	foreach ($modrights_res['set'] AS $mrk => $mrv) {
		$tmprights = unserializeBroken($mrv['options']);
		if (key_exists($mrv['guid'], $_SESSION['wspvars']['modrights'])) {
			$_SESSION['wspvars']['rights'][$mrv['guid']] = $tmprights[$_SESSION['wspvars']['modrights'][$mrv['guid']]];
		} else {
			$_SESSION['wspvars']['rights'][$mrv['guid']] = 0;
		}
	}
} else {
	if ($_SERVER['SCRIPT_NAME']!=str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/login.php"))) {
		echo "»»logout cause no rights»»";
//		header("location: ".str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/logout.php")));
//		die();
	}
}

if (key_exists('lockstat',$_SESSION['wspvars'])) {
	if ($_SESSION['wspvars']['lockstat']=="images") { $_SESSION['wspvars']['lockstat'] = "imagesfolder"; }
	if ($_SESSION['wspvars']['lockstat']=="download") { $_SESSION['wspvars']['lockstat'] = "downloadfolder"; }
}

// setup publisher (as preview) as option, if user is allowed to change contents
if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('contents', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['contents']>0 && ((array_key_exists('publisher', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['publisher']==0) || !(array_key_exists('publisher', $_SESSION['wspvars']['rights'])))) {
	$_SESSION['wspvars']['rights']['publisher']=(100+intval($_SESSION['wspvars']['rights']['contents']));
}

// ueberpruefung, ob sich der benutzer unberechtigt auf die seite "geschlichen" hat
if ((isset($_SESSION['wspvars']['lockstat']) && $_SESSION['wspvars']['lockstat']!="") && (array_key_exists($_SESSION['wspvars']['lockstat'], $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights'][$_SESSION['wspvars']['lockstat']]=="0") && $_SESSION['wspvars']['usertype']!=1):
	require ("./data/include/header.inc.php");
    require ("./data/include/navbar.inc.php");
    require ("./data/include/sidebar.inc.php");
	
    addWSPMsg('errormsg', 'lockstat page');

    ?>
    <div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('contentstructure headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('contentstructure info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
        </div>
    </div>
    </div>
	<?php
	include ("./data/include/footer.inc.php");
	die();
endif;

// ueberprueung, ob die seite durch andere benutzer geoeffnet ist
// und ggf. seitensperre, wenn die seite dies erfordert
// anderer User in Seiteneinstellungen => Zugang verwehren
if ($_SESSION['wspvars']['fposcheck'] && isset($fileusage_userid) && isset($fileusage_usevar) && $fileusage_usevar!=$_SESSION['wspvars']['usevar']):
	if (isset($_POST['takeover']) && isset($_POST['takeovertype']) && (intval($_POST['takeovertype'])==1)):
		$sql = "DELETE FROM `security` WHERE `usevar` = '".escapeSQL($fileusage_usevar)."'";
		doSQL($sql);
	else:
		require (DOCUMENT_ROOT."/".WSP_DIR."/data/include/errorhandler.inc.php");
        require (DOCUMENT_ROOT."/".WSP_DIR."/data/include/siteinfo.inc.php");
		require (DOCUMENT_ROOT."/".WSP_DIR."/data/include/header.inc.php");
		require (DOCUMENT_ROOT."/".WSP_DIR."/data/include/navbar.inc.php");
        require (DOCUMENT_ROOT."/".WSP_DIR."/data/include/sidebar.inc.php");
		?>
        <!-- MAIN -->
        <div class="main">
            <!-- MAIN CONTENT -->
            <div class="main-content">
                <div class="content-heading clearfix">
                    <div class="heading-left">
                        <h1 class="page-title"><?php echo returnIntLang('userlock headline'); ?></h1>
                        <p class="page-subtitle"><?php echo returnIntLang('userlock info'); ?></p>
                    </div>
                    <ul class="breadcrumb">
                        <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                        <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
                    </ul>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div>
                            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                                <input type="hidden" name="takeovertype" value="1" />
                                <?php foreach ($_REQUEST AS $rk => $rv):
                                    echo "<input type='hidden' name='".$rk."' value='".$rv."' />";
                                endforeach; ?>
                                <input type="submit" name="takeover" value="<?php echo returnIntLang('override userlock', false); ?>" class="btn btn-danger" />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
		require (DOCUMENT_ROOT."/".WSP_DIR."/data/include/footer.inc.php");
		die();
	endif;
endif;

if (array_key_exists('userid', $_SESSION['wspvars'])) {
	if (!strstr(dirname($_SERVER['PHP_SELF']),'xajax')){
		// save user position to security table to prevent double user access while changing contents or prefs
		$sql = "UPDATE `security` SET `position` = '".escapeSQL($_SESSION['wspvars']['fpos'])."' WHERE `usevar` = '".escapeSQL($_SESSION['wspvars']['usevar'])."'";
		doSQL($sql);
		// save user position to security log
		$sql = "INSERT INTO `securitylog` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `lastposition` = '".escapeSQL($_SESSION['wspvars']['fpos'])."', `lastaction` = 'start loading', `lastchange` = ".time();
		doSQL($sql);
	}
}
?>