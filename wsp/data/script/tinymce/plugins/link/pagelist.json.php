<?php
/**
 * TINYMCE pagelist.json for "link"-plugin
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8.1
 * @lastchange 2019-02-11
 */

session_start();

include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/dbaccess.inc.php';

	function getMenuLevelTiny($parent, $spaces, $modi, $aSelectIDs = array(), $op = '', $gmlVisible = 1) {
		$menulevel_sql = "SELECT `mid`, `position`, `visibility`, `description`, `connected` FROM `menu` WHERE trash != 1 AND `connected` = ".intval($parent)." ORDER BY `position`";
		$menulevel_res = doSQL($menulevel_sql);
		
		if ($menulevel_res['num']>0) {
			$spacer = "";
			for ($i=0; $i<$spaces; $i++) {
                $spacer.= " "; 
            }
            foreach ($menulevel_res['set'] AS $mlrsk => $mlrsv) {
                $menuItem = "";
				if (is_array($op)) {
					if (count($op)>0) {
						if (in_array($mlrsv['mid'], $op)) {
							if (($gmlVisible==0 && $mlrsv['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $mlrsv['visibility']==1)) {
								$menuItem = "{title: '".$spacer.$mlrsv['description']."', value: '[%PAGE:".$mlrsv['mid']."%]'},\n";
                            }			
						}
                    } else {
						if (($gmlVisible==0 && $mlrsv['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $mlrsv['visibility']==1)) {
							$menuItem = "{title: '".$spacer.$mlrsv['description']."', value: '[%PAGE:".$mlrsv['mid']."%]'},\n";
                        }
                    }
				} else {
					if (($gmlVisible==0 && $mlrsv['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $mlrsv['visibility']==1)) {
						$menuItem = "{title: '".$spacer.$mlrsv['description']."', value: '[%PAGE:".$mlrsv['mid']."%]'},\n";
					}
				}
				
				if ($op!='xajax') {
					echo $menuItem;
				} else {
					$GLOBALS['getMenuLevelTiny']['finalmenu'].= $menuItem;
				}
				
				if ($spaces=="-1"):
					getMenuLevelTiny($mlrsv['mid'], $spaces, $modi, $aSelectIDs, $op);
				else:
					if (!isset($getsubs)):
						getMenuLevelTiny($mlrsv['mid'], $spaces+3, $modi, $aSelectIDs, $op);
					elseif ($getsubs):
						getMenuLevelTiny($mlrsv['mid'], $spaces+3, $modi, $aSelectIDs, $op);
					endif;
				endif;
				$i++;
	
            }	// foreach
        }	// if
    }	// getMenuLevelTiny()

echo "[\n";
getMenuLevelTiny(0, '', gmlSelect);
echo "]";

//[
//    {title: 'My page 1', value: '[%PAGE1%]'},
//    {title: ' My page 2', value: '[%PAGE2%]'},
//    {title: '  My page 3', value: '[%PAGE3%]'},
//    {title: ' My page 4', value: '[%PAGE47%]'}
//]

// EOF ?>