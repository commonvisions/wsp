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

    $editable = doResultSQL("SELECT `editable` FROM `menu` WHERE `mid` = ".intval($_POST['mid']));

    if ($editable==1 || $editable==9) {
        $cid = array(0); // grep all used cid
        $realtemp = getTemplateID(intval($_POST['mid']));
        $templatevars = getTemplateVars($realtemp);
        $contentvardesc = unserializeBroken(getWSPProperties(array('contentvardesc')));
        // output sortable list 
        if ($templatevars!==false) {
            echo "<div class='dd'><ul class='dd-list content-sortable' mid='".intval($_POST['mid'])."'>";
            foreach ($templatevars['contentareas'] AS $tvck => $tvcv) {
                echo "<li class='dd-item custom-item dd-area ";
                if ($tvck==0) { echo " dd-disabled "; }
                echo "' id='area-".intval($_POST['mid'])."-".intval($tvcv)."'><div class='custom-content' style='padding-left: 15px;'>";
                if (isset($contentvardesc[$tvcv]) && trim($contentvardesc[$tvcv])!='') {
                    echo trim($contentvardesc[$tvcv]);
                }
                else {
                    echo returnIntLang('str contentarea')." ".($tvcv+1);
                }
                echo "<div class='right' style='padding-right: 15px;'>";
                echo "<a onclick=\"createContent(".intval($_POST['mid']).",".intval($tvcv).");\"><i class='fa fa-plus-square'></i> ".returnIntLang('contents add content')."</a>";
                echo "</div>";
                echo "</div>";
                echo "</li>\n";
                // select contents related to mid
                $consel_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($tvcv)." AND `trash` = 0 AND (`content_lang` = '".escapeSQL(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '') ORDER BY `position`";
                $consel_res = doSQL($consel_sql);
                if ($consel_res['num']>0) {
                    // run all contentareas with their contents
                    foreach ($consel_res['set'] AS $csk => $csv) {
                        // setup content information array
                        echo returnContentItem($csv);
                        $cid[] = $csv['cid'];
                    }
                }
            }
        } else {
            echo "<div class='dd'><ul class='dd-list content-sortable' mid='".intval($_POST['mid'])."'><li>";
            echo returnIntLang('contents no template associated to this menupoint');
        }
        // get contents that are not associated to given areas
        $consel_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `trash` = 0 AND (`content_lang` = '".escapeSQL(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '') AND `cid` NOT IN (".implode(',', $cid).") ORDER BY `position`";
        $consel_res = doSQL($consel_sql);
        if ($consel_res['num']>0):
            echo "</ul></div>";
            echo "<hr />";
            echo "<div class='dd'><ul class='dd-list content-nodrop' mid='".intval($_POST['mid'])."'>";
            echo "<li class='dd-item custom-item dd-area dd-disabled' id='area-".intval($_POST['mid'])."-unresolved'><div class='custom-content' style='padding-left: 15px;'>".returnIntLang('contents unresolved contents')."</div></li>\n";
            if ($consel_res['num']>0):
                // run all contentareas with their contents
                foreach ($consel_res['set'] AS $csk => $csv):
                    // setup content information array
                    echo returnContentItem($csv);
                endforeach;
            endif;
        endif;
        echo "</ul></div>";
        echo "\n";
    }
    else if ($editable==7) {
        echo returnIntLang('contents this section is dynamic');
    }
    else {
        echo returnIntLang('contents this section is not editable');
    }
}

// EOF