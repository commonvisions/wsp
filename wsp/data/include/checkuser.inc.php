<?php
/**
 * check login
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-08
 */

// zu lange inaktive user ausloggen und temporaere daten loeschen
// nur bei autologout 13-11-13
$inactive_sql = "SELECT `sid`, `usevar` FROM `security` WHERE `timevar` < ".(time()-(60*60));
$inactive_res = doSQL($inactive_sql);
if ($inactive_res['num']>0) {
    for ($ires=0; $ires<$inactive_res['num']; $ires++) {
        $sql = "DELETE FROM `security` WHERE `sid` = ".intval($inactive_res['set'][$ires]['sid']);
        doSQL($sql);
    }
}

// login-status der aktuellen usevar pruefen
$secure_num = 0;
if (array_key_exists('usevar', $_SESSION['wspvars'])) {
	$secure_sql = "SELECT * FROM `security` WHERE `usevar` = '".$_SESSION['wspvars']['usevar']."'"; //'
	$secure_res = doSQL($secure_sql);
	$secure_num = intval($secure_res['num']);
}

if ($secure_num!=0):
	/* found sessionvar => user is logged in */
	$_SESSION['wspvars']['actusersid'] = $secure_res['set'][0]['sid'];
	$_SESSION['wspvars']['userid'] = $secure_res['set'][0]['userid'];
	$_SESSION['wspvars']['logintime'] = $secure_res['set'][0]['logintime'];
	// erklaerung !!!	
	if (array_key_exists('fposcheck', $_SESSION['wspvars']) && $_SESSION['wspvars']['fposcheck'] && array_key_exists('fpos', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['fpos'])!=''):
		$fposition_num = 0;
		$fposition_sql = "SELECT * FROM `security` WHERE `position` = '".escapeSQL($_SESSION['wspvars']['fpos'])."' AND `usevar` != '".escapeSQL($_SESSION['wspvars']['usevar'])."'";
		$fposition_res = doSQL($fposition_sql);
		if ($fposition_res['num']>0):
			$fileusage_userid = $fposition_res['set'][0]['userid'];
			$fileusage_usevar = $fposition_res['set'][0]['usevar'];
		endif;
	endif;
	$indexlogin = true;
else:
	// if user visits a page except login page without beeing logged in -> return him to logout page
	if ($_SERVER['SCRIPT_NAME']!=str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasedir']."/index.php"))):
		header("location: ".str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasedir']."/logout.php")));
		die();
	endif;
endif;

$rights_num = 0;
if (array_key_exists('userid', $_SESSION['wspvars'])):
	$rights_sql = "SELECT * FROM `restrictions` WHERE `rid` = ".$_SESSION['wspvars']['userid'];
	$rights_res = doSQL($rights_sql);
	$rights_num = intval($rights_res['num']);
endif;

if ($rights_num==1) {
	$_SESSION['wspvars']['usertype'] = @$rights_res['set'][0]['usertype']; // replaces $usertype
	$_SESSION['wspvars']['realname'] = @$rights_res['set'][0]['realname']; // replaces $realname
	$_SESSION['wspvars']['messages'] = @$rights_res['set'][0]['usernotice']; // replaces $messages
	$_SESSION['wspvars']['disablenews'] = intval(@$rights_res['set'][0]['disablenews']); // added 2015-01-23
	$_SESSION['wspvars']['saveprops'] = intval(@$rights_res['set'][0]['saveprops']); // added 2015-01-23
	if (!(key_exists('modrights', $_SESSION['wspvars']))):
		$_SESSION['wspvars']['modrights'] = array(); 
	endif;
	// festlegung der allgemeinen rechte
	$temp_menuid_cols = unserializeBroken($rights_res['set'][0]['idrights']);
	if (is_array($temp_menuid_cols)):
		foreach ($temp_menuid_cols AS $key => $value):
			$_SESSION['wspvars']['rights'][$key] = 2;
			$_SESSION['wspvars']['rights'][$key."_id"] = implode(",", $value);
			$_SESSION['wspvars']['rights'][$key."_array"] = $value;
		endforeach;
		foreach (unserializeBroken($rights_res['set'][0]["rights"]) as $key => $value):
			$temp_menuid_rights[$key] = array();
			if (strlen($key)!=36):
				$_SESSION['wspvars']['rights'][$key] = $value;
				if ($value>1):
					$_SESSION['wspvars']['rights'][$key."_id"] = $temp_menuid_rights[$key];
				endif;
			else:
				$_SESSION['wspvars']['modrights'][$key] = $value;
			endif;
		endforeach;
	else:
		$temp_menuid_cols = @explode("\n", $rights_res['set'][0]["idrights"]);
		if (is_array($temp_menuid_cols)):
			for ($m=0;$m<count($temp_menuid_cols);$m++):
				$temp_menuid_elements = @explode(":", $temp_menuid_cols[$m]);
				if (is_array($temp_menuid_elements)):
					if (key_exists(1, $temp_menuid_elements)):
						$temp_menuid_rights[$temp_menuid_elements[0]] = $temp_menuid_elements[1];
					endif;
				endif;
			endfor;
		endif;
		foreach (unserializeBroken($rights_res['set'][0]["rights"]) as $key => $value):
			if (strlen($key)!=36):
				$_SESSION['wspvars']['rights'][$key] = $value;
				if ($value>1):
					$_SESSION['wspvars']['rights'][$key."_id"] = $temp_menuid_rights[$key];
				endif;
			else:
				$_SESSION['wspvars']['modrights'][$key] = $value;
			endif;
		endforeach;
	endif;
	//
	// festlegung modularer rechte
	//
	$modrights_sql = "SELECT `guid`, `possibilities` FROM `wsprights`";
	$modrights_res = doSQL($modrights_sql);
	if ($modrights_res['num']>0) {
        foreach ($modrights_res['set'] AS $mresk => $mresv) {
            $tmprights = unserializeBroken($mresv['possibilities']);
            if (key_exists(trim($mresv['guid']), $_SESSION['wspvars']['modrights'])) {
                $_SESSION['wspvars']['rights'][trim($mresv['guid'])] = $tmprights[$_SESSION['wspvars']['modrights'][trim($mresv['guid'])]];
            }
            else {
                $_SESSION['wspvars']['rights'][trim($mresv['guid'])] = 0;
            }
        }
    }
}
else {
	if ($_SERVER['SCRIPT_NAME']!=str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasedir']."/index.php"))) {
        header("location: ".str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasedir']."/logout.php")));
        die();
	}
}

if (key_exists('lockstat',$_SESSION['wspvars'])) {
	if ($_SESSION['wspvars']['lockstat']=="images") { $_SESSION['wspvars']['lockstat'] = "imagesfolder"; }
	if ($_SESSION['wspvars']['lockstat']=="download") { $_SESSION['wspvars']['lockstat'] = "downloadfolder"; }
	if ($_SESSION['wspvars']['lockstat']=="flash") { $_SESSION['wspvars']['lockstat'] = "flashfolder"; }
}

// setup publisher (as preview) as option, if user is allowed to change contents
if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('contents', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['contents']>0 && ((array_key_exists('publisher', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['publisher']==0) || !(array_key_exists('publisher', $_SESSION['wspvars']['rights'])))):
	$_SESSION['wspvars']['rights']['publisher']=(100+intval($_SESSION['wspvars']['rights']['contents']));
endif;

// ueberpruefung, ob sich der benutzer unberechtigt auf die seite "geschlichen" hat
if ((array_key_exists('lockstat', $_SESSION['wspvars']) && $_SESSION['wspvars']['lockstat']!="") && (array_key_exists($_SESSION['wspvars']['lockstat'], $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights'][$_SESSION['wspvars']['lockstat']]=="0") && $_SESSION['wspvars']['usertype']!=1):
	include ("data/include/header.inc.php");
	include ("data/include/wspmenu.inc.php");
	?>
	<div id="contentholder"><fieldset class="errormsg">Diese Einstellungen k&ouml;nnen von Ihnen nicht ver&auml;ndert werden. Bitte w&auml;hlen Sie aus dem Men&uuml;, welche Bereiche Sie bearbeiten d&uuml;rfen.</fieldset></div>
	<?php
	include ("data/include/footer.inc.php");
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
		require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
		require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
		require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/header.inc.php");
		require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wspmenu.inc.php");
		?>
		<div id="contentholder"><fieldset class="noticemsg">Es ist bereits ein Benutzer angemeldet und bearbeitet zur Zeit diese Daten! Wenn Sie als letzter Benutzer in diesem Bereich waren und den Browser geschlossen haben, ohne sich auszuloggen, oder Sie sich sicher sind, das durch die Übernahme kein Datenverlust auftreten wird, k&ouml;nnen Sie die Bearbeitung &uuml;bernehmen.</fieldset>
		<fieldset class="options innerfieldset">
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
				<input type="hidden" name="takeovertype" value="1" />
				<?php foreach ($_REQUEST AS $rk => $rv):
					echo "<input type='hidden' name='".$rk."' value='".$rv."' />";
				endforeach; ?>
				<input type="submit" name="takeover" value="Bearbeitung &uuml;bernehmen" />
			</form>
		</fieldset></div>
		<?php
		include ("./data/include/footer.inc.php");
		die();
	endif;
endif;

if (array_key_exists('userid', $_SESSION['wspvars'])):
	// save user position to security table to prevent double user access while changing contents or prefs
	$sql = "UPDATE `security` SET `timevar` = '".time()."', `position` = '".$_SESSION['wspvars']['fpos']."' WHERE `usevar` = '".$_SESSION['wspvars']['usevar']."'";
	doSQL($sql);
	// save user position to security log
	$sql = "INSERT INTO `securitylog` SET `uid` = '".$_SESSION['wspvars']['userid']."', `lastposition` = '".$_SESSION['wspvars']['fpos']."', `lastaction` = 'start loading', `lastchange` = '".time()."'";
	doSQL($sql);
endif;

// EOF ?>