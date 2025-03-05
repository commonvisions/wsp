<div class="col-md-12">
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo returnIntLang('str userinfo'); ?></h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-3">
                    <?php echo returnIntLang('str username'); ?>
                </div>
                <div class="col-md-3">
                    <div class="input-group form-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        <input name="change_username" id="change_username" type="text" value="<?php echo $saved_username; ?>" maxlength="16" class="form-control" placeholder="<?php echo returnIntLang('rights usernamehint'); ?>" />
                    </div>
                </div>
                <?php if($saved_usertype!=22) { ?>
                    <div class="col-md-3">
                        <?php echo returnIntLang('str email'); ?>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group form-group">
                            <span class="input-group-addon">@</span>
                            <input name="change_realmail" id="change_realmail" type="text" value="<?php echo $saved_realmail; ?>" maxlength="200" class="form-control" placeholder="<?php echo returnIntLang('rights emailhint'); ?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <?php echo returnIntLang('str realname'); ?>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group form-group">
                            <span class="input-group-addon"><i class="far fa-user"></i></span>
                            <input name="change_realname" id="change_realname" type="text" value="<?php echo $saved_realname; ?>" maxlength="200" class="form-control" />
                        </div>
                    </div>
                    <?php if ($saved_usertype!=1): ?>
                        <div class="col-md-3">
                            <?php echo returnIntLang('usermanagement prepos'); ?>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select name="predefined" id="predefined_position" size="1" class="form-control">
                                    <option value="undefined"><?php echo returnIntLang('usermanagement prepos noselect', false); ?></option>
                                    <option value="developer"><?php echo returnIntLang('usermanagement prepos developer', false); ?></option>
                                    <option value="technics"><?php echo returnIntLang('usermanagement prepos technician', false); ?></option>
                                    <option value="seo"><?php echo returnIntLang('usermanagement prepos seo', false); ?></option>
                                    <option value="redaktion"><?php echo returnIntLang('usermanagement prepos editor', false); ?></option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="row">
            <?php } ?>
                <div class="col-md-3">
                    <?php echo returnIntLang('rights setnewpass'); ?> 
                </div>
                <div class="col-md-3">
                    <div class="input-group form-group">
                        <span class="input-group-addon"><input name="change_password" type="hidden" value="0" /><input name="change_password" id="change_password" type="checkbox" value="1" /></span>
                        <input name="set_newpass" id="set_newpass" type="text" value="<?php echo strtoupper(substr(md5(date("YmdHis")),0,8)); ?>" maxlength="32" class="form-control" />
                    </div>
                </div>
            <?php if($saved_usertype!=22) { ?>
                <div class="col-md-6">
                    <?php echo returnIntLang('rights setnewpasshint'); ?> <input name="email_password" type="hidden" value="0" /><input name="email_password" id="email_password" type="checkbox" value="1" />
                </div>
            <?php } ?>
            </div>
        </div>
    </div>
</div>