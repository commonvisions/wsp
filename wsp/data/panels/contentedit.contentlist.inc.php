<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title">
            <?php 
            echo returnIntLang('contentedit existing contents 1'); 
            echo returnIntLang('contentedit existing contents 2');
            ?>
        </h3>
        <?php panelOpener(true, array(), false, 'contentexists'); ?>
    </div>
    <div class="panel-body" >
        <?php 

        $oc_sql = "SELECT `mid`, `content_area` FROM `content` WHERE `cid` = ".intval($cid);
        $oc_res = doSQL($oc_sql);
        $fp_num = 0;
        if ($oc_res['num']>0) {
            $_SESSION['wspvars']['editmenuid'] = intval($oc_res['set'][0]['mid']);
            $activeca = intval($oc_res['set'][0]['content_area']);
            $fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval($oc_res['set'][0]['mid']);
            $fp_num = getNumSQL($fp_sql);
        }

        if ($fp_num>0) {
            $realtemp = getTemplateID(intval($oc_res['set'][0]['mid']));
            $templatevars = getTemplateVars($realtemp);

            $siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
            $siteinfo_res = doResultSQL($siteinfo_sql);
            if ($siteinfo_res!==false):
                $contentvardesc = unserializeBroken($siteinfo_res);
            endif;

            echo returnContents(intval($oc_res['set'][0]['mid']), $contentinfo_res['set'][0]['cid'], array(), '', 1);

            /*
            //
            // disabled 2019-08-05
            //
            foreach ($templatevars['contentareas'] AS $tk => $tv) {

                echo "<table class='tablelist'><tr><td class='tablecell two head'>";
                if (isset($contentvardesc) && is_array($contentvardesc)) {
                    if (array_key_exists(($tv-1), $contentvardesc) && trim($contentvardesc[($tv-1)])!='') {
                        echo $contentvardesc[($tv-1)];
                    }
                    else {
                        echo returnIntLang('contentstructure contentarea', false)." ".$tv;
                    }
                }
                else {
                    echo returnIntLang('contentstructure contentarea', false)." ".$tv;
                }
                echo "</td><td class='tablecell two head'>".returnIntLang('str description', false)."</td><td class='tablecell three head'>".returnIntLang('str lastchange', false)."</td><td class='tablecell one head'>".returnIntLang('str action', false)."</td></tr></table>";

                $consel_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($oc_res['set'][0]['mid'])." AND `content_area` = ".intval($tv)." AND (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' OR `content_lang` = '') AND `trash` = 0 ORDER BY `position`";
                $consel_res = doSQL($consel_sql);

                echo "<div id='area-".$tk."'>";
                echo "<table class='tablelist'>";
                foreach ($consel_res['set'] AS $csrk => $csrv) {
                    echo "<tr>";
                    $contentdesc = '';
                    $interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` WHERE `guid` = '".escapeSQL($csrv['interpreter_guid'])."'";
                    $interpreter_res = doSQL($interpreter_sql);
                    if ($interpreter_res['num']>0):
                        $contentdesc = $interpreter_res['set'][0]['name'];
                    elseif ($csrv['interpreter_guid']=='genericwysiwyg'):
                        $contentdesc = returnIntLang('hint generic wysiwyg', false);
                    endif;
                    echo "<td class=\"tablecell two\">".$contentdesc."</td>";
                    $valuedesc = '';
                    $contentvalue = unserializeBroken($csrv['valuefields']);
                    if (is_array($contentvalue) && array_key_exists('desc', $contentvalue)):
                        $valuedesc.= trim($contentvalue['desc']);
                    endif;
                    if (intval($csrv['globalcontent_id'])>0):
                        $valuedesc.= " [GlobalContent]";
                    endif;
                    echo "<td class=\"tablecell two\">".$valuedesc."</td>";

                    echo "<td class=\"tablecell three\">&nbsp;</td>";
                    echo "<td class='tablecell one'>";
                    $cfe_num = 0; // checkforedit
                    $cfe_sql = "SELECT `sid` FROM `security` WHERE `position` = '".escapeSQL("/".WSP_DIR."/contentedit.php;cid=".intval($csrv['cid']))."'";
                    $cfe_res = doSQL($cfe_sql);
                    echo " <span class=\"bubblemessage ";
                    if ($cfe_res['num']>0): echo " orange "; else: echo " green "; endif;
                    echo "\" onclick=\"document.getElementById('editcontentid').value = '".intval($csrv['cid'])."'; document.getElementById('editcontents').submit();\">".returnIntLang('bubble edit', false)."</span>";
                    echo "&nbsp;</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            }
            */


        }

        ?>
    </div>
</div>