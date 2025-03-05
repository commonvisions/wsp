<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2020-07-09
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/siteinfo.inc.php");
    require("../data/include/clsinterpreter.inc.php");

    if (isset($_POST['mid']) && intval($_POST['mid'])>0) {
        // check for selected page if mid is given, otherwise first page of structure
        $fp_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($_POST['mid'])." AND `editable` = 1";
        $fp_res = doSQL($fp_sql);
        if ($fp_res['num']==0) {
            $fp_sql = "SELECT `mid` FROM `menu` WHERE `connected` = 0 AND `editable` = 1";
            $fp_res = doSQL($fp_sql);
        }
        if ($fp_res['num']>0) {
            $realtemp = getTemplateID(intval($fp_res['set'][0]['mid']));
            $templatevars = getTemplateVars($realtemp);
            ?>
<div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h4 class="modal-title"><?php echo returnIntLang('contentstructure create new content', true)?></h4>
        </div>
        <div class="modal-body">
            <form method="post" enctype="multipart/form-data" id="formnewcontent">
                <p><?php echo returnIntLang('contentstructure page', true); ?></p>
                <p><select name="mid" id="insertpage" class="form-control fullselect fullwidth" onchange="updateCreateContent(this.value, 0);">
                    <?php

                    $datatable = 'menu';
                    $mid_res = doSQL("SELECT `mid` FROM `".$datatable."` WHERE `connected` = 0 AND `editable` = 1 ORDER BY `position`");
                    foreach ($mid_res['set'] AS $mk => $mv):
                        echo returnStructureItem($datatable, $mv['mid'], true, 9999, array(intval($_POST['mid'])), 'option', array('visible'=>2));
                    endforeach;

                    ?>
                </select></p>
                <?php 
            
            if (isset($templatevars) && is_array($templatevars) && count($templatevars['contentareas'])>0) { 
                echo '<input type="hidden" name="op" value="add" />';
                echo '<input type="hidden" name="lang" value="'.$_SESSION['wspvars']['workspacelang'].'" />';
            }
            else {
                echo "<p>".returnIntLang('contentstructure this menupoint has no template with contentvars defined')."</p>";
            }
            
            if (isset($templatevars) && is_array($templatevars) && count($templatevars['contentareas'])>0): ?>
                    <p><?php echo returnIntLang('contentstructure contentarea', true); ?></p>
                    <p><select name="carea" id="insertarea" class="form-control singleselect fullwidth" onchange="updateCreateContent($('#insertpage').val(), this.value);"><?php

                        $contentvardesc = unserializeBroken(getWSPProperties(array('contentvardesc')));
                        foreach ($templatevars['contentareas'] AS $cak => $carea):
                            echo "<option value=".$carea." ";
                            if (intval($_POST['carea'])==$carea): echo " selected=\"selected\" "; endif;
                            echo ">";
                            if (isset($contentvardesc) && is_array($contentvardesc)):
                                if (array_key_exists(($carea), $contentvardesc) && trim($contentvardesc[($carea)])!=''):
                                    echo $contentvardesc[($carea)];
                                else:
                                    echo returnIntLang('contentstructure contentarea', false)." ".$carea."</option>";
                                endif;
                            else:
                                echo returnIntLang('contentstructure contentarea', false)." ".$carea."</option>";
                            endif;
                        endforeach;

                    ?></select></p>
                    <p><?php echo returnIntLang('contentstructure paste before', true); ?></p>
                    <p><select name="posvor" id="posvor" class="form-control singleselect fullwidth"><?php

                    // select contents ..
                    $consel_sql = "SELECT `interpreter_guid`, `globalcontent_id`, `description`, `valuefields`, `position` FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `trash` = 0 AND `content_area` = ".((isset($_POST['carea']))?intval($_POST['carea']):0)." ORDER BY `position`";
                    $consel_res = doSQL($consel_sql);
                    if ($consel_res['num']>0) {
                        foreach ($consel_res['set'] AS $csrsk => $csrsv) {
                            $interpreter_sql = "SELECT `guid`, `name` FROM `interpreter` WHERE `guid` = '".escapeSQL($csrsv['interpreter_guid'])."'";
                            $interpreter_res = doSQL($interpreter_sql);
                            if ($interpreter_res['num']>0) {
                                $intinfo = array($interpreter_res['set'][0]['name']);
                            } else {
                                $intinfo = array(returnIntLang('interpreter '.$csrsv['interpreter_guid'], false));
                            }
                            // get description
                            $contentdesc = trim($csrsv['description']);
                            if (trim($contentdesc)=='') {
                                $contentvalue = unserializeBroken($csrsv['valuefields']);
                                if (isset($contentvalue['desc'])) { $contentdesc = trim($contentvalue['desc']); }
                            }
                            if (trim($contentdesc)!="") {
                                $intinfo[] = trim($contentdesc);
                            }
                            if (intval($csrsv['globalcontent_id'])>0) {
                                $intinfo[] = "[Global]";
                            }
                            echo "<option value=\"".intval($csrsv['position'])."\">".implode(" - ", $intinfo)."</option>";
                        }
                    }
                    echo "<option value=\"0\" selected=\"selected\">".returnIntLang('contentstructure paste atend', true)."</option>"; 

                        ?></select></p>
                        <?php // show up a list of selectable content elements / interpreters ?>
                        <p><?php echo returnIntLang('contentstructure new element', true); ?></p>
                        <p><select name="sid" id="sid" class="form-control searchselect fullwidth">
                            <optgroup label="<?php echo returnIntLang('hint generic interpreter', false); ?>">
                                <option value="genericwysiwyg"><?php echo returnIntLang('hint generic wysiwyg', false); ?></option>
                                <option value="modularcontent"><?php echo returnIntLang('hint generic modularcontent', false); ?></option>
                            </optgroup>
                            <optgroup label="<?php echo returnIntLang('hint interpreter', false); ?>">
                                <?php
                                $interpreter_sql = "SELECT `guid`, `name` FROM `interpreter` ORDER BY `name`";
                                $interpreter_res = doSQL($interpreter_sql);
                                if ($interpreter_res['num']>0) {
                                    $classname = "";
                                    foreach ($interpreter_res['set'] AS $irsk => $irsv) {
                                        echo "<option value=\"".$irsv['guid']."\">".trim($irsv['name'])."</option>\n";
                                    }
                                }
                            ?></select>
                            <?php // hidden field with global content id = 0 to set a value if no following (if avaiable) global content is set ?>
                            <input type="hidden" name="gcid" id="" value="0" /></p>
                            <?php 
                            // check if global contents are avaiable 
                            $gc_sql = "SELECT * FROM `content_global` WHERE (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' || `content_lang` = '') AND `trash` = 0 ORDER BY `interpreter_guid`";
                            $gc_res = doSQL($gc_sql);
                            if ($gc_res['num']>0) { 
                ?>
                <p><?php echo returnIntLang('contentstructure global content', true); ?></p>
                                <p><select name="gcid" id="gcid" class="form-control singleselect fullwidth">
                                    <option value="0"><?php echo returnIntLang('contentstructure choose globalcontent', false); ?></option>
                                    <?php
                                    foreach ($gc_res['set'] AS $grsk => $grsv) {

                                        $fieldvalue = unserializeBroken($grsv['valuefields']);
                                        $i_sql = "SELECT `parsefile`, `name` FROM `interpreter` WHERE `guid` = '".escapeSQL(trim($grsv['interpreter_guid']))."'";
                                        $i_res = doSQL($i_sql);
                                        if ($i_res['num']>0) {
                                            $file = trim($i_res['set'][0]['parsefile']);
                                            $name = trim($i_res['set'][0]['name']);
                                            if (is_file(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."interpreter".DIRECTORY_SEPARATOR.$file)) {
                                                include (DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."interpreter".DIRECTORY_SEPARATOR.$file);
                                                $clsInterpreter = new $interpreterClass;
                                                echo "<option value=\"".intval($grsv["id"])."\">".$name;
                                                $desc = $clsInterpreter->getView($fieldvalue);
                                                if (trim($desc)!='') {
                                                    echo " - ".trim($desc);
                                                }
                                                echo "</option>";
                                            }
                                        }
                                        else {
                                            echo "<option value=\"".intval($grsv['id'])."\">".returnIntLang('hint generic wysiwyg', false);
                                            echo " - ".$fieldvalue['desc'];
                                            echo "</option>";
                                        }
                                    }
                                    ?>
                                </select></p>
                                <?php 
                            } ?>
                <?php endif; ?>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo returnIntLang('str cancel', false); ?></button>
            <?php if (is_array($templatevars) && count($templatevars['contentareas'])>0): ?>
                <button type="button" class="btn btn-primary" onclick="checkData();" class="btn btn-primary"><?php echo returnIntLang('str create', false); ?></button>
            <?php endif; ?>
        </div>
    </div>
</div>
        <?php
        }
    }
}
else {
	echo "timeout|false";
}

// EOF ?>