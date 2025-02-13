<?php
/**
 * content finden
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-18
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)) {
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

    require $wspdir.'/data/include/globalvars.inc.php';
    require $wspdir.'/data/include/wsplang.inc.php';
    require $wspdir."/data/include/dbaccess.inc.php";
    require $wspdir."/data/include/funcs.inc.php";
    require $wspdir."/data/include/filesystemfuncs.inc.php";
    require $wspdir."/data/include/errorhandler.inc.php";
    require $wspdir."/data/include/siteinfo.inc.php";

    if (isset($_REQUEST['searchval']) && trim($_REQUEST['searchval'])!='') {

        $_SESSION['wspvars']['searchcontent'] = trim($_REQUEST['searchval']);
        if (!(isset($_REQUEST['searchlang']))) {
            $_REQUEST['searchlang'] = 'de';
        }

        $midset = array('cid' => array(),'gcid' => array(),'intid' => array());
        $cidset = array();

        if (intval($_REQUEST['searchval'])>0) {
            // search by ID
            $c_sql = "SELECT c.`cid`, c.`mid` FROM `content` AS c, `menu` AS m WHERE c.`cid` = ".intval($_REQUEST['searchval']);
            if (isset($_SESSION['wspvars']['rights']['contents_array']) && count($_SESSION['wspvars']['rights']['contents_array'])>0):
                // limit results to allowed contents
                $c_sql.= " AND c.`mid` IN ('".implode("','", $_SESSION['wspvars']['rights']['contents_array'])."') ";
            endif;
            $c_sql.= " AND (c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` = m.`mid`) AND (c.`content_lang` = '".trim($_REQUEST['searchlang'])."' OR c.`content_lang` = '')";
        }
        else {
            // search by CONTENT
            $c_sql = "SELECT c.`cid`, c.`mid` FROM `content` AS c, `menu` AS m  WHERE ((c.`valuefields` LIKE '%".escapeSQL(strtolower(trim($_REQUEST['searchval'])))."%') ";
            if (isset($_SESSION['wspvars']['rights']['contents_array']) && count($_SESSION['wspvars']['rights']['contents_array'])>0):
                // limit results to allowed contents
                $c_sql.= " AND c.`mid` IN ('".implode("','", $_SESSION['wspvars']['rights']['contents_array'])."') ";
            endif;
            $c_sql.= " AND (c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` = m.`mid`) AND (c.`content_lang` = '".trim($_REQUEST['searchlang'])."' OR c.`content_lang` = ''))";
        }
        $c_res = doSQL($c_sql);

        if ($c_res['num']>0) {
            foreach ($c_res['set'] AS $crsk => $crsv):
                $midset['cid'][] = intval($crsv['mid']);
                $cidset[] = intval($crsv['cid']);
            endforeach;
            $midset['cid'] = array_unique($midset['cid']);
        }
        // search by CONTENT in globalcontents
        if (intval($_REQUEST['searchval'])==0) {
            $gc_sql = "SELECT id FROM `globalcontent` WHERE ((`valuefield` LIKE '%".escapeSQL(strtolower(trim($_REQUEST['searchval'])))."%') ";
            $gc_sql.= " AND `trash` = 0 AND (`content_lang` = '".trim($_REQUEST['searchlang'])."' OR c.`content_lang` = ''))";
            $gc_res = doSQL($gc_sql);
            if ($gc_res['num']>0):
                foreach ($gc_res['set'] AS $gcrsk => $gcrsv):
                    $c_sql = "SELECT c.`cid`, c.`mid` FROM `content` AS c, `menu` AS m WHERE (c.`globalcontent_id` = ".intval($gcrsv['id'])." ";
                    if (isset($_SESSION['wspvars']['rights']['contents_array']) && count($_SESSION['wspvars']['rights']['contents_array'])>0):
                        // limit results to allowed contents
                        $c_sql.= " AND `mid` IN ('".implode("','", $_SESSION['wspvars']['rights']['contents_array'])."') ";
                    endif;
                    $c_sql.= " AND c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` = m.`mid` AND (`content_lang` = '".trim($_REQUEST['searchlang'])."' OR `content_lang` = ''))";
                    $c_res = doSQL($c_sql);
                    if ($c_res['num']>0):
                        foreach ($c_res['set'] AS $crsk => $crsv):
                            $midset['gcid'][] = intval($crsv['mid']);
                            $cidset[] = intval($crsv['cid']);
                        endforeach;
                        $midset['gcid'] = array_unique($midset['gcid']);
                    endif;
                endforeach;
            endif;
        }
        // search by NAME in interpreter
        if (intval($_REQUEST['searchval'])==0) {
            $i_sql = "SELECT `guid` FROM `interpreter` WHERE `name` LIKE '%".escapeSQL(strtolower(trim($_REQUEST['searchval'])))."%' OR `parsefile` LIKE '%".escapeSQL(strtolower(trim($_REQUEST['searchval'])))."%php'";
            $i_res = doSQL($i_sql);
            if ($i_res['num']>0) {
                foreach ($i_res['set'] AS $irsk => $irsv) {
                    $c_sql = "SELECT c.`cid`, c.`mid` FROM `content` AS c, `menu` AS m WHERE (c.`interpreter_guid` = '".escapeSQL($irsv['guid'])."' ";
                    if (isset($_SESSION['wspvars']['rights']['contents_array']) && count($_SESSION['wspvars']['rights']['contents_array'])>0):
                        // limit results to allowed contents
                        $c_sql.= " AND `mid` IN ('".implode("','", $_SESSION['wspvars']['rights']['contents_array'])."') ";
                    endif;
                    $c_sql.= " AND c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` = m.`mid` AND (`content_lang` = '".trim($_REQUEST['searchlang'])."' OR `content_lang` = ''))";
                    $c_res = doSQL($c_sql);
                    if ($c_res['num']>0) {
                        foreach ($c_res['set'] AS $crsk => $crsv) {
                            $midset['intid'][] = intval($crsv['mid']);
                            $cidset[] = intval($crsv['cid']);
                        }
                        $midset['intid'] = array_unique($midset['intid']);
                    }
                }
            }
        }
        
        // output
        echo '<table class="tablelist">';
        // if there are found menupoints with CONTENT ID
        if (count($midset['cid'])>0) { 
            echo "<tr><td class='tablecell eight head'>";
            if (count($midset['cid'])>1): echo returnIntLang('contentstructure found contents1')." ".count($midset['cid'])." ".returnIntLang('contentstructure found contents2'); else: echo returnIntLang('contentstructure found content1')." ".count($midset['cid'])." ".returnIntLang('contentstructure found content2'); endif;
            echo "</td></tr>";
            foreach ($midset['cid'] AS $ck => $cv) {

                $cd_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($cv)." AND `cid` IN ('".implode("','", $cidset)."')";
                $cd_res = doSQL($cd_sql);
                $contentdesc = "";
                $interpreterdesc = '';
                if ($cd_res['num']>0) {
                    $valuefields = unserializeBroken($cd_res['set'][0]['valuefields']);
                    if (isset($valuefields['desc']) && trim($valuefields['desc'])!='') {
                        $contentdesc = $valuefields['desc'];
                    }
                    $id_sql = "SELECT `name` FROM `interpreter` WHERE `guid` = '".escapeSQL($cd_res['set'][0]['interpreter_guid'])."'";
                    $id_res = doSQL($id_sql);
                    if ($id_res['num']>0) {
                        $interpreterdesc = trim($id_res['set'][0]['name'])." » "; 
                    }
                    $md_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($cv);
                    $md_res = doSQL($md_sql);
                    if ($md_res['num']>0) { 
                        $menudesc = trim($md_res['set'][0]['description']); 
                    }
                    ?>
                    <tr>
                        <td class="tablecell two"><span class="bubblemessage  green " onclick="document.getElementById('editcontentid').value = '<?php echo intval($cd_res['set'][0]['cid']); ?>'; document.getElementById('editcontents').submit();">✎</span></td>
                        <td class="tablecell two"><?php echo $menudesc; ?></td>
                        <td class="tablecell two"><?php echo $interpreterdesc.$contentdesc; ?></td>
                        <td class="tablecell two"><?php echo returnIntLang('str contentarea')." ".trim($cd_res['set'][0]['content_area']); ?></td>
                    </tr>
                    <?php
                }
            }
        }
        // if there are found menupoints with GLOBALCONTENT ID    
        if (count($midset['gcid'])>0): 
            echo "<tr><td class='tablecell eight head'>".returnIntLang('contentstructure found globalcontents')."</td></tr>";
        endif;
        
        // if there are found menupoints by INTERPRETER NAME   
        if (count($midset['intid'])>0) { 
            echo "<tr><td class='tablecell eight head'>".returnIntLang('contentstructure foundfound interpreter')."</td></tr>";
            foreach ($midset['intid'] AS $ck => $cv) {
                $contentdesc = ''; $menudesc = ''; $interpreterdesc = '';
                $cd_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($cv)." AND `cid` IN (".implode(",", $cidset).")";
                $cd_res = doSQL($cd_sql);
                if ($cd_res['num']>0) {
                    $valuefields = unserializeBroken($cd_res['set'][0]['valuefields']);
                    if (isset($valuefields['desc']) && trim($valuefields['desc'])!='') {
                        $contentdesc = $valuefields['desc'];
                    }
                    $id_sql = "SELECT `name` FROM `interpreter` WHERE `guid` = '".trim($cd_res['set'][0]['interpreter_guid'])."'";
                    $id_res = doResultSQL($id_sql);
                    if ($id_res!==false && trim($id_res)!='') {
                        $interpreterdesc = trim($id_res)." » ";
                    }
                }
                $md_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($cv);
                $md_res = doResultSQL($md_sql);
                if ($md_res!==false): $menudesc = trim($md_res); endif;
                ?>
                <tr>
                    <td class="tablecell two"><span class="bubblemessage  green " onclick="document.getElementById('editcontentid').value = '<?php echo intval($cd_res['set'][0]['cid']); ?>'; document.getElementById('editcontents').submit();">✎</span></td>
                    <td class="tablecell two"><?php echo $menudesc; ?></td>
                    <td class="tablecell two"><?php echo $interpreterdesc.$contentdesc; ?></td>
                    <td class="tablecell two"><?php echo returnIntLang('str contentarea')." ".$cd_res['set'][0]['content_area']; ?></td>
                </tr>
            <?php 
            }
        }
        
        if (count($midset['cid'])==0 && count($midset['gcid'])==0 && count($midset['intid'])==0) {
            echo "<tr style='display: none;'><td class='tablecell eight hidden'></td></tr><tr><td class='tablecell eight'>".returnIntLang('contentstructure no contents found')."</td></tr>";
        }
        echo "</table>";
    }
    else if (isset($_REQUEST['searchval'])) {
        $_SESSION['wspvars']['searchcontent'] = trim($_REQUEST['searchval']);
    }
}
