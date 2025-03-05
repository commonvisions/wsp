<?php
$userdata_sql = "SELECT * FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
$userdata_res = doSQL($userdata_sql);
?>
<div class="panel" id="panel-change-userdata">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('usermanagement changeuserdata1'); echo " »".$userdata_res['set'][0]['user']."« "; echo returnIntLang('usermanagement changeuserdata2'); ?></h3>
        <?php panelOpener(true, array(), false, 'panel-change-userdata'); ?>
    </div>
    <div class="panel-body">
        <form method="post" id="frmuseredit">
            <div class="row">
                <div class="col-md-12">
                    <label><?php echo returnIntLang('usermanagement showname'); ?></label>
                    <div class="input-group form-group">
                        <span class="input-group-addon"><i class="far fa-user"></i></span>
                        <input class="form-control" name="my_new_realname" type="text" value="<?php echo prepareTextField($userdata_res['set'][0]['realname']); ?>" />
                        <input name="my_act_realname" type="hidden" value="<?php echo prepareTextField($userdata_res['set'][0]['realname']); ?>" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label><?php echo returnIntLang('usermanagement oldpass'); ?></label>
                    <div class="input-group form-group">
                        <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                        <input class="form-control" name="my_act_pass" type="password" value="" />
                    </div>
                </div>
                <div class="col-md-6">
                    <label><?php echo returnIntLang('usermanagement newpass'); ?></label>
                    <div class="input-group form-group">
                        <span class="input-group-addon"><i class="fa fa-unlock"></i></span>
                        <input class="form-control" id="my_new_pass" name="my_new_pass" type="text" value="" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <p><label class="checkbox-inline">
					   <input name="my_message_disable" type="hidden" value="0" /><input name="my_message_disable" type="checkbox" value="1" <?php if((isset($_SESSION['wspvars']['disablenews']) && $_SESSION['wspvars']['disablenews']==1) || (isset($userdata_res['set'][0]['disablenews']) && $userdata_res['set'][0]['disablenews']==1)) echo "checked='checked'"; ?> /> <?php echo returnIntLang('usermanagement disable mailmessage'); ?>
				    </label></p>
                </div>
                <div class="col-md-6">
                    <p><label class="checkbox-inline">
                        <input name="my_save_session" type="hidden" value="0" /><input name="my_save_session" type="checkbox" value="1" <?php if((isset($_SESSION['wspvars']['saveprops']) && $_SESSION['wspvars']['saveprops']==1) || (isset($userdata_res['set'][0]['saveprops']) && $userdata_res['set'][0]['saveprops']==1)) echo "checked='checked'"; ?> /> <?php echo returnIntLang('usermanagement save session'); ?>
                    </label></p>
                </div>
                <div class="col-md-6">
                    <p><label class="checkbox-inline">
                        <input name="my_help_disable" type="hidden" value="0" /><input name="my_help_disable" type="checkbox" value="1" <?php if((isset($_SESSION['wspvars']['disablehelp']) && $_SESSION['wspvars']['disablehelp']==1) || (isset($userdata_res['set'][0]['disablehelp']) && $userdata_res['set'][0]['disablehelp']==1)) echo "checked='checked'"; ?> /> <?php echo returnIntLang('usermanagement disable legend'); ?>
                    </label></p>
                </div>
            </div>
			<input type="hidden" name="self_data" value="changeself" />
        </form>
        <p><input type="button" onclick="checklengthpass();" class="btn btn-primary" value="<?php echo returnIntLang('usermanagement updateprops', false); ?>" /></p>
    </div>
</div>
<?php

unset($userdata_sql);
unset($userdata_res);