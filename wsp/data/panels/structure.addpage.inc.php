<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('structure createnewmenupoint'); ?></h3>
    </div>
    <div class="panel-body" >
        <form method="post" id="formnewmenuitem" enctype="multipart/form-data">
            <div class="form-horizontal">
                <div class="col-md-12" id="newmenu_item" style="margin-top: 0px;">
                    <div class="form-group input-group">
                        <span class="input-group-addon"><a onclick="toggleItemList()"><i class="fa fa-bars"></i></a></span>
                        <input type="text" placeholder="<?php echo returnIntLang('structure newmenupointname', false); ?>" name="newmenuitem" id="newmenuitem" value="" class="form-control" />
                    </div>
                </div>
                <div class="col-md-12" id="newmenu_list" style="display: none;">
                    <div class="form-group input-group">
                        <span class="input-group-addon"><a onclick="toggleItemList()"><i class="fa fa-minus"></i></a></span>
                        <textarea placeholder="<?php echo returnIntLang('structure newmenupointlist', false); ?>" rows="6" name="newmenuitemlist" id="newmenuitemlist" class="form-control" ></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-12">
                        <select id="subpointfrom" name="subpointfrom" size="1" class="searchselect fullwidth">
                            <?php if ($_SESSION['wspvars']['rights']['sitestructure']==1): ?>
                                <?php 
                                
                                echo "<option value='0'>".returnIntLang('structure menuedit mainmenu')."</option>";
                                echo returnStructureItem('menu', 0, true, 9999, ((isset($menueditdata['connected']))?array(intval($menueditdata['connected'])):array()), 'option'); 
                            
                                ?>
                            <?php elseif ($_SESSION['wspvars']['rights']['sitestructure']==7 && intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])>0): ?>
                                <option value="<?php echo intval($_SESSION['wspvars']['rights']['sitestructure_array'][0]); ?>"><?php 

                                $mpname_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])." ORDER BY `level`, `position`";
                                $mpname_res = mysql_query($mpname_sql);
                                if ($mpname_res):
                                    $mpname_num = mysql_num_rows($mpname_res);
                                endif;
                                if ($mpname_num>0):
                                    echo mysql_result($mpname_res, 0, 'description'); 
                                    ?></option>
                                    <?php getMenuLevel($_SESSION['wspvars']['rights']['sitestructure_array'][0], (mysql_result($mpname_res, 0, 'level')*3), 1);
                                endif;
                            endif;?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-12">
                        <select name="template" id="template" class="singleselect fullwidth">
                            <option value="-1"><?php echo returnIntLang('structure pleasechoosetemplate', false); ?></option>
                            <option value="0" selected="selected"><?php echo returnIntLang('structure chooseuppertemplate', false); ?></option>
                            <?php

                            $tpl_def = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'"));
                            $tpl_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
                            $tpl_res = doSQL($tpl_sql);
                            foreach ($tpl_res['set'] AS $tplk => $tplv):
                                if ($tplv['id']==$tpl_def):
                                    echo "<option value=\"".$tplv['id']."\" selected=\"selected\">".$tplv['name']." [".returnIntLang('structure standardtemplate', false)."]</option>\n";
                                else:
                                    echo "<option value=\"".$tplv['id']."\">".$tplv['name']."</option>\n";
                                endif;
                            endforeach;

                            ?>
                        </select>
                    </div>
                </div>

            </div>
            <input type="hidden" name="mid" value="0" /><input type="hidden" name="op" value="new" />
            <p><button onclick="createNewMP(); return false;" class="btn btn-primary"><?php echo returnIntLang('str create', false); ?></button></p>
      </form>
    </div>
</div>
<script language="JavaScript" type="text/javascript">
<!--

function createNewMP() {
    if (document.getElementById('newmenuitem').value!='' || document.getElementById('newmenuitemlist').value!='') {
        document.getElementById('formnewmenuitem').submit();
        }
    else { 
        alert(unescape('<?php echo returnIntLang('structure please fill in menupoint or list of menupoints', false); ?>')); document.getElementById('newmenuitem').focus(); 
        }
    return false;
    }

function toggleItemList() {
    if ($('#newmenu_item').css('display')=='none') {
        $('#newmenuitemlist').val('');
        $('#newmenu_list').css('display','none');
        $('#newmenu_item').css('display','block');
    }
    else {
        var itemVal = $('#newmenuitem').val();
        $('#newmenuitem').val('');
        if ($.trim(itemVal)!='') {
            $('#newmenuitemlist').val(itemVal);
        }
        $('#newmenu_list').css('display','block');
        $('#newmenu_item').css('display','none');
    }
    }

// -->
</script>


