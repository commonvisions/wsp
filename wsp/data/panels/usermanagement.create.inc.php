<div class="panel" id="panel-create-user">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('usermanagement createnew'); ?></h3>
        <?php panelOpener(true, array(), false, 'panel-create-user'); ?>
    </div>
    <div class="panel-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmcreateuser">
            <div class="row">
                <div class="col-md-6">
                    <label><?php echo returnIntLang('str username'); ?></label>
                    <div class="input-group form-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        <input class="form-control" name="new_username" id="new_username" type="text" placeholder="<?php echo returnIntLang('usermanagement username desc', false); ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <label><?php echo returnIntLang('usermanagement prepos'); ?></label>
                    <div class="input-group form-group">
                        <select name="new_position" id="new_position" size="1" class="form-control singleselect fullwidth">
                            <optgroup name="<?php echo returnIntLang('usermanagement position', false); ?>" label="<?php echo returnIntLang('usermanagement position', false); ?>">
                                <option value="admin"><?php echo returnIntLang('usermanagement prepos admin', false); ?></option>
                                <option value="developer"><?php echo returnIntLang('usermanagement prepos developer', false); ?></option>
                                <option value="technics"><?php echo returnIntLang('usermanagement prepos technician', false); ?></option>
                                <option value="seo"><?php echo returnIntLang('usermanagement prepos seo', false); ?></option>
                                <option value="redaktion"><?php echo returnIntLang('usermanagement prepos editor', false); ?></option>
                                <option value="webuser"><?php echo returnIntLang('usermanagement prepos webuser', false); ?></option>
                            </optgroup>
                            <?php

                            $cloneuser_sql = "SELECT * FROM `restrictions` WHERE `rid` != '".$_SESSION['wspvars']['userid']."' AND `usertype` != 1 ORDER BY `user` ASC";
                            $cloneuser_res = doSQL($cloneuser_sql);

                            if ($cloneuser_res['num']) {
                                echo "<option value=''>".returnIntLang('usermanagement prepos noselect', false)."</option>";
                                echo "<optgroup label=\"".returnIntLang('usermanagement clonerights', false)."\" name=\"".returnIntLang('usermanagement clonerights', false)."\">";
                                for ($cres=0; $cres<$cloneuser_res['num']; $cres++):
                                    echo "<option value=\"".intval($cloneuser_res['set'][$cres]['rid'])."\">".$cloneuser_res['set'][$cres]['realname']."</option>";
                                endfor;
                                echo "</optgroup>";
                            }

                            ?>
                            
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label><?php echo returnIntLang('str email'); ?></label>
                    <div class="input-group form-group">
                        <span class="input-group-addon">@</span>
                        <input class="form-control" name="new_email" id="new_email" type="text" value="" placeholder="<?php echo returnIntLang('usermanagement email desc', false); ?>" />
                    </div>
                </div>
                <div class="col-md-6">
                    <label><?php echo returnIntLang('usermanagement realname'); ?></label>
                    <div class="form-group">
                        <input class="form-control" name="new_realname" id="new_realname" type="text" placeholder="" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <input type="hidden" name="op" value="user_new" />
                    <input type="hidden" name="user_data" value="setup" />
                    <p><input type="button" onclick="checklengthuser();" class="btn btn-primary" value="<?php echo returnIntLang('usermanagement createnew', false); ?>" /></p>
                </div>
            </div>
        </form>
        <script>

        function checkForUnixNames(givenValue, checkedField, fieldName) {
            var tempValue = '';
            var errorCount = 0;
            for (g=0; g<givenValue.length; g++) {
                if (givenValue[g] < "0" || givenValue[g] > "9") {
                    if (givenValue[g] < "a" || givenValue[g] > "z") {
                        if (givenValue[g] < "A" || givenValue[g] > "Z") {
                            if (givenValue[g] != "." && givenValue[g] != "_") {
                                errorCount++;
                            }
                            else {
                                tempValue += givenValue[g];
                            }
                        }
                        else {
                            tempValue += givenValue[g];
                        }
                    }
                    else {
                        tempValue += givenValue[g];
                    }
                }
                else {
                    tempValue += givenValue[g];
                }
            }
            if (errorCount > 0) {
                alert ("Bitte verwenden Sie im Feld '" + fieldName + "' nur Buchstaben ('a-z'), Zahlen ('0-9'), Punkt ('.') und/oder Unterstrich '_'");
                document.getElementById(checkedField).value = tempValue;
                return false;
            }
            else {
                return true;
            }
        }

        function checklengthuser(){
            if(document.getElementById('new_username').value.length>2){
                if (checkForUnixNames($('#new_username').val(), 'new_username', 'Username')) {
                    if(document.getElementById('new_realname').value.length>1){
                        if(document.getElementById('new_email').value.length>8){
                            $('#frmcreateuser').submit();
                            return false;
                        }
                        else{
                            alert("<?php echo returnIntLang('usermanagement new user setup email', false); ?>");
                            return false;
                        }
                    }
                    else if ($('#new_position').val()=='webuser') {
                        $('#frmcreateuser').submit();
                        return false;
                    } 
                    else {
                        alert("<?php echo returnIntLang('usermanagement new user setup real name', false); ?>");
                        return false;
                    }
                    document.getElementById('frmcreateuser').submit(); return false;
                    return false;
                }
            }
            else {
                alert("<?php echo returnIntLang('usermanagement new username too short', false); ?>");
                return false;
            }
        }

        function checklengthpass(){
            if (document.getElementById('my_new_pass').value.length>7 || document.getElementById('my_new_pass').value.length==0) {
                document.getElementById('frmuseredit').submit(); return false;
            } 
            else {
                alert("Das Passwort muss min. 8 Zeichen enthalten");
            }
        }

        </script>
    </div>
</div>