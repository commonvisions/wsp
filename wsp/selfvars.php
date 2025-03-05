<?php
/**
 * Verwaltung von eigenen Variablen
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-07-25
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-paint-brush',returnIntLang('menu design'),returnIntLang('menu design selfvars'));
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    'summernote-wsp.css',
    );
$_SESSION['wspvars']['addpagejs'] = array(
    '/summernote/summernote.min.js',
    '/jquery/jquery.autogrowtextarea.js'
    );
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
$op = checkparamvar('op');

/* define page specific functions ------------ */

// variable speichern
if ($op=='savevar'):
    $varname = trim(checkparamvar('varname'));
    $shortcut = urltext(trim(checkparamvar('shortcut', false, false, false, urltext($varname))));
    if (checkparamvar('id')>0):
        $sql = "UPDATE `selfvars` SET ".
            "`name` = '".escapeSQL($varname)."', ".
            "`shortcut` = '".escapeSQL($shortcut)."', ".
            "`selfvar` = '".escapeSQL($_POST['selfvar'])."' ".
            "WHERE `id` = ".intval(checkparamvar('id'));
		$res = doSQL($sql);
        if ($res['aff']==1):
            addWSPMsg('resultmsg', returnIntLang('selfvar updated'));
            $useselfvars_sql = "SELECT `id` FROM `templates` WHERE `template` LIKE '%[%".strtoupper($shortcut)."%]%' ORDER BY `name`";
            $useselfvars_res = getResultSQL($useselfvars_sql);
            if ($useselfvars_res!==false && count($useselfvars_res)>0):
                foreach ($useselfvars_res AS $ures):
                    if (intval($ures)>0):
                        // set contentchanged to right value -> template changed 
                        $sql = "UPDATE `menu` SET `contentchanged` = 5 WHERE `templates_id` = ".intval($ures);
                        doSQL($sql);
                    endif;
                endforeach;
                addWSPMsg('resultmsg', returnIntLang('selfvar publish affected templates'));
            endif;
        endif;
    else:
        $sql = "INSERT INTO `selfvars` SET ".
            "`name` = '".escapeSQL($varname)."', ".
            "`shortcut` = '".escapeSQL($shortcut)."', ".
            "`selfvar` = '".escapeSQL($_POST['selfvar'])."'";
		doSQL($sql);
    endif;
    $op = 'edit';
endif;

if ($op=='delvar' && checkparamvar('id')>0):
    $sql = "DELETE FROM `selfvars` WHERE `id` = ".checkparamvar('id');
	$res = doSQL($sql);
    if ($res['aff']>0): 
        addWSPMsg('resultmsg', returnIntLang('selfvar deleted'));
    endif;
endif;

include ("data/include/header.inc.php");
include ("data/include/navbar.inc.php");
include ("data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('selfvars headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('selfvars info'); ?> <?php echo returnIntLang('selfvars globalcontent info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <div class="row">
                <?php
	           
                $selfvars_sql = "SELECT * FROM `selfvars` ORDER BY `name`";
                $selfvars_data = doSQL($selfvars_sql);
                
                if ($selfvars_data['num']>0 && $op!='edit') {
                ?>
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('selfvars existingvars'); ?></h3>
                            <?php panelOpener(false, array('op',array('edit'),array(false)), true, 'existingvars'); ?>
                        </div>
                        <div class="panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo returnIntLang('selfvars varname'); ?></th>
                                        <th><?php echo returnIntLang('selfvars vartype'); ?></th>
                                        <th><?php echo returnIntLang('selfvars shortcut'); ?></th>
                                        <th><?php echo returnIntLang('str usage'); ?></th>
                                        <th class="text-right"><?php echo returnIntLang('str action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 0;
                                    foreach($selfvars_data['set'] AS $svk => $svv):
                                        $i++;
                                        echo "<tr>\n";
                                        echo "<td><a href=\"".$_SERVER['PHP_SELF']."?op=edit&id=".$svv['id']."\">".$svv['name']."</a></td>";

                                        $checkfortags = strip_tags($svv['selfvar']);
                                        if ($checkfortags==$svv['selfvar']):
                                            echo "<td>".returnIntLang('selfvars text')."</td>";
                                        else:
                                            $checkforphp = stristr($svv['selfvar'], "?".">");
                                            if ($checkforphp==FALSE):
                                                echo "<td>".returnIntLang('selfvars html')."</td>";
                                            else:
                                                echo "<td>".returnIntLang('selfvars php')."</td>";
                                            endif;
                                        endif;

                                        echo "<td><em>".$svv['shortcut']."</em></td>";

                                        $useselfvars_sql = "SELECT `id`, `name` FROM `templates` WHERE `template` LIKE '%[%".strtoupper($svv['shortcut'])."%]%' ORDER BY `name`";
                                        $useselfvars_data = doSQL($useselfvars_sql);

                                        if ($useselfvars_data['num']!=0):
                                            echo "<td class=\"td two\">";
                                            foreach ($useselfvars_data['set'] AS $usk => $usv):
                                                echo "<a href=\"templates.php?op=edit&id=".$usv['id']."\">".$usv['name']."</a><br />";
                                            endforeach;
                                            echo "</td>";
                                        else:
                                            echo "<td>-</td>";
                                        endif;

                                        echo "<td class='text-right'><a href=\"".$_SERVER['PHP_SELF']."?op=edit&id=".$svv['id']."\"><i class='fa fa-pencil-alt fa-btn' aria-hidden='true'></i></a> <a href=\"".$_SERVER['PHP_SELF']."?op=delvar&id=".$svv['id']."\" onclick=\"return confirm(unescape('".returnIntLang('selfvars confirm delete selfvar', false);
                                        if ($useselfvars_data['num']!=0):
                                            echo " ".returnIntLang('selfvars deleting selfvar will cause remove this selfvar from used templates', false);
                                        endif;
                                        echo "'));\"><i class='fa fa-trash fa-btn' aria-hidden='true'></i></a></div>";
                                        echo "</td>\n";
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
	
            <?php

            if ($op=="edit" && checkparamvar('id')>0):
                $selvars_sql = "SELECT * FROM `selfvars` WHERE `id` = ".intval(checkparamvar('id'));
                $selvars_data = doSQL($selvars_sql);

                if ($selvars_data['num']!=0):
                    $id = $selvars_data['set'][0]['id'];
                    $varname = $selvars_data['set'][0]['name'];
                    $selfvar = stripslashes($selvars_data['set'][0]['selfvar']);
                    $shortcut = $selvars_data['set'][0]['shortcut'];
                    if (trim($shortcut)==''): $shortcut = urltext($varname); endif;
                else:
                    $id = 0;
                    $selfvar = '';
                    $varname = '';
                    $shortcut = '';
                endif;
            elseif ($op=="edit"):
                $id = 0;
                $selfvar = '';
                $varname = '';
                $shortcut = '';
            endif;

            if ($op=="edit"):  ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php if($op=="edit" && $id>0): ?><?php echo returnIntLang('selfvars editvar'); ?><?php else: ?><?php echo returnIntLang('selfvars createnewvar'); ?><?php endif; ?></h3>
                            </div>
                            <div class="panel-body">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="formvar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="varname" id="varname" value="<?php echo $varname; ?>" placeholder="<?php echo returnIntLang('selfvars varname'); ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="shortcut" id="shortcut" value="<?php echo $shortcut; ?>" placeholder="<?php echo returnIntLang('selfvars shortcut', false); ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <textarea class="form-control autogrow allowTabChar summernote" name="selfvar" id="selfvar" placeholder="<?php echo returnIntLang('selfvars varcontent'); ?>"><?php echo $selfvar; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="op" value="savevar" /><input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <p><a href="#" onclick="checkEditFields(); return false;"><input type="button" value="<?php echo returnIntLang('str save', false); ?>" class="btn btn-primary" /></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" onclick="document.getElementById('varname').value=''; document.getElementById('selfvar').value='';"><input type="button" value="<?php echo returnIntLang('str cancel', false); ?>" class="btn btn-warning" /></a></p>
            <?php else: ?>
                <p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?op=edit&id=0"><input type="button" value="<?php echo returnIntLang('selfvars createnewvar', false); ?>" class="btn btn-primary" /></a></p>
            <?php endif; ?>


        </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script type="text/javascript" language="javascript">
<!--
    
$(document).ready(function() {

    /*
    $('.summernote').summernote({
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['misc', ['fullscreen','codeview','undo','redo']]
        ],
        
        height: 300,
        callbacks: {
            onInit: function() {
                $('.summernote').summernote('codeview.activate');
                $('.summernote').summernote('code', '<?php echo str_replace(PHP_EOL, "\n", prepareTextField($selfvar)); ?>');
            }
        },
        codemirror: { // codemirror options
            theme: 'monokai'
        },
        hint: {
            words: ['apple', 'orange', 'watermelon', 'lemon'],
            match: /\b(\w{1,})$/,
            search: function (keyword, callback) {
                callback($.grep(this.words, function (item) {
                    return item.indexOf(keyword) === 0;
                }));
            }
        },
    });
    
    $('textarea.note-codable').on('change', function(){
        console.log($(this));
    })
    */
    
    $(".allowTabChar").allowTabChar();
    $('.autogrow').autoGrow();
    
});

    
function checkEditFields() {
    if ($('#varname').val()=='') {
		alert('<?php echo returnIntLang('selfvars pleasefillvarname', false); ?>');
		document.getElementById('varname').focus();
		return false;
	}	// if

	document.getElementById('formvar').submit();
	return true;
	}	// checkEditFields()
//-->
</script>

<?php include ("data/include/footer.inc.php"); ?>