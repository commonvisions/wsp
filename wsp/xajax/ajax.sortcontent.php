<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2021-06-02
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/siteinfo.inc.php");
    require("../data/include/clsinterpreter.inc.php");

    // $_POST['copy'] » if shift was set
    // $_POST['mid'] » target mid, that got a content element dropped
    // $_POST['dataset'] » json object with a list of all elements (missing the first 'area')    
    
    $do['copy'] = (trim($_POST['copy'])==='true')?true:false;
    $do['mid'] = intval($_POST['mid']);
    $do['affmid'] = array($do['mid']);
    $do['areas'] = array();
    $do['areadata'] = array();
    $do['areas'] = getTemplateVars(getTemplateID($do['mid']))['contentareas'];
    $do['data'] = json_decode(trim($_POST['dataset']));
    $c=0; // first element in area set => area set will be translated with values of $do['areas'] to put the contents to corrects content areas
    if (is_array($do['data'])) {
        foreach ($do['data'] AS $dk => $dv) {
            if (substr(trim($dv),0,4)=='area') {
                $c++;
            } else {
                $do['areadata'][$c][] = array(
                    // get the (old) mid of the content
                    'mid' => doResultSQL("SELECT `mid` FROM `content` WHERE `cid` = ".intval($dv)),
                    'cid' => intval($dv),
                    // get the (old) content area of the content
                    'coa' => doResultSQL("SELECT `content_area` FROM `content` WHERE `cid` = ".intval($dv)),
                );
            }
        }
    }
    unset($do['data']);
    foreach ($do['areas'] AS $dok => $dov) {
        if (isset($do['areadata'][$dok]) && is_array($do['areadata'][$dok])) {
            $do['content'][$dov] = $do['areadata'][$dok];
        }
        else {
            $do['content'][$dov] = array();
        }
    }
//  unset($do['areas']);
//  unset($do['areadata']);
    
    if ($do['copy']) {
        $aff = false;
        // create a var to hold all affected cid => so if a cid is doubled THIS content was copied on same mid
        $affcid = array();
        $action = 'copy';
        // if there will be no double THE content was copied from another mid
        foreach ($do['content'] AS $dck => $dcv) {
            foreach ($dcv AS $dcvk => $dcvv) {
                if (!(in_array($dcvv['cid'], $affcid))) {
                    if (intval($dcvv['mid'])==$do['mid']) {
                        $res = doSQL("UPDATE `content` SET `mid` = ".$do['mid'].", `content_area` = ".$dck.", `position` = ".($dcvk+1)." WHERE `cid` = ".$dcvv['cid']);
                        // fill array with affected mids to return to page and do some information update over there 
                        $do['affmid'][] = intval($dcvv['mid']);
                        if ($res['aff']>0) {
                            // set affected true to return output finally
                            $aff = true;
                        }
                    } 
                    else {
                        // the mid variant
                        $action = 'copymid';
                        // get the existing content to duplicate
                        $contentdata = doSQL("SELECT * FROM `content` WHERE `cid` = ".$dcvv['cid']);
                        // call function that inserts new contentdata-set to target-mid, content_area (and optional $lang) 
                        // return will be inserted integer»id OR boolean»false
                        $res = insertContents($contentdata['set'][0], $do['mid'], $dck, ($dcvk+1));
                        if ($res>0 && $res!==false) {
                            $aff = true;
                        } 
                        else if ($res===false) {
                            echo json_encode(array('action' => 'error', 'target' => 'copymid'));
                            die();
                        }
                    }
                } 
                else {
                    // the $affcid variant
                    $action = 'copycid';
                    
                    var_export($do);
                    
                    echo "content was doubled on same mid";
                    
                }
                // fill array with affected mids to return to page and do some information update over there 
                $do['affmid'][] = intval($dcvv['mid']);
                if ($res['aff']>0) {
                    // set affected true to return output finally
                    $aff = true;
                }
            }
        }
        $do['affmid'] = array_values(array_unique($do['affmid']));
        if ($aff) {
            echo json_encode(array('action' => $action, 'mid' => $do['affmid']));
        }
    } else {
        // the mid of the content element changes and ALL elements have to be reordered
        $aff = false;
        foreach ($do['content'] AS $dck => $dcv) {
            foreach ($dcv AS $dcvk => $dcvv) {
                $res = doSQL("UPDATE `content` SET `mid` = ".$do['mid'].", `content_area` = ".$dck.", `position` = ".($dcvk+1)." WHERE `cid` = ".$dcvv['cid']);
                // fill array with affected mids to return to page and do some information update over there 
                $do['affmid'][] = intval($dcvv['mid']);
                if ($res['aff']>0) {
                    // set affected true to return output finally
                    $aff = true;
                }
            }
        }
        $do['affmid'] = array_values(array_unique($do['affmid']));
        if ($aff) {
            echo json_encode(array('action' => 'sort', 'mid' => $do['affmid']));
        }
    }
}

// EOF