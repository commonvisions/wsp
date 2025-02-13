<?php
/**
 * TINYMCE classlist.json for "link"-plugin
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8.1
 * @lastchange 2019-02-11
 */

session_start();

include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/dbaccess.inc.php';

function getClasses() {
	$classes = "";
	$classes_array = array();
	$cls_sql = "SELECT `stylesheet` FROM `stylesheets` WHERE `stylesheet`!=''";
	$cls_res = doSQL($cls_sql);
	if($cls_res['num']>0) {
		foreach ($cls_res['set'] AS $clsk => $clsv) {
			$org_cls_value = trim($clsv['stylesheet']);
			preg_match_all('/(\.\w+).*{/', $org_cls_value, $res);
			foreach($res AS $key => $value) {
				if(is_array($value) && count($value)>0) {
					foreach($value AS $vkey => $vvalue) {
						$vv_tmp = str_replace("{","",$vvalue);
						$vv_tmp = str_replace(","," ",$vv_tmp);
						$vv_tmp = str_replace(":"," ",$vv_tmp);
						$vv_tmp = explode(" ",$vv_tmp);
						foreach($vv_tmp AS $k2 => $v2) {
							$classes_array[] = $v2;
						}
					}
				}
			}
		}
	}
	
	$classes_array = array_unique($classes_array);
	foreach($classes_array AS $k3 => $v3) {
		if($v3!="" && substr($v3,0,1)==".") {
			$classes .= "{text: '" . $v3 . "', value: '" . substr($v3,1) . "'},\n";
        }
    }
	return $classes;
}

	echo "[
";
	echo getClasses();
	echo "]";

// EOF ?>