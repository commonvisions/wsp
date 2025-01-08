<?php
/**
 * TINYMCE tableclasslist.json for "table"-plugin
 * @author COVI
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.7
 * @lastchange 2018-09-19
 */

session_start();

include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/dbaccess.inc.php';

function getClasses() {
	$classes = "";
	$classes_array = array();
	$cls_sql = "SELECT `stylesheet` FROM `stylesheets` WHERE `stylesheet`!=''";
	$cls_res = mysql_query($cls_sql);
	if($cls_res):
		$cls_num = mysql_num_rows($cls_res);
	endif;
	if($cls_num>0):
		for($c=0;$c<$cls_num;$c++):
			$org_cls_value = mysql_result($cls_res,$c);
//			preg_match_all('/(\w+)?(\s*>\s*)?(#\w+)?\s*(\.\w+)?\s*/', $org_cls_value, $res);
			preg_match_all('/(\.\w+).*{/', $org_cls_value, $res);
			
			foreach($res AS $key => $value):
				if(is_array($value) && count($value)>0):
					foreach($value AS $vkey => $vvalue):
						$vv_tmp = str_replace("{","",$vvalue);
						$vv_tmp = str_replace(","," ",$vv_tmp);
						$vv_tmp = str_replace(":"," ",$vv_tmp);
						$vv_tmp = explode(" ",$vv_tmp);
						foreach($vv_tmp AS $k2 => $v2):
							$classes_array[] = $v2;
						endforeach;
					endforeach;
				endif;
			endforeach;
		endfor;
	endif;
	
	$classes_array = array_unique($classes_array);
	foreach($classes_array AS $k3 => $v3):
		if($v3!="" && substr($v3,0,1)=="."):
			$classes .= "{text: '" . $v3 . "', value: '" . substr($v3,1) . "'},";
		endif;
	endforeach;
	
	
	
	return $classes;
}


	echo "[
";
	echo getClasses();
	echo "]";


//[
//    {text: 'Schmal', value: 'schmal'},
//    {text: 'Mittel', value: 'mittel'},
//    {text: 'Breit', value: 'breit'},
//    {text: 'Letzter', value: 'last'}
//]

// EOF ?>