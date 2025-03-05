<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('usermanagement manageexisting'); ?></h3>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo returnIntLang('str user'); ?></th>
                    <th><?php echo returnIntLang('usermanagement regmail'); ?></th>
                    <th><?php echo returnIntLang('str rights'); ?></th>
                    <th><?php echo returnIntLang('str action'); ?></th>
                </tr>
            </thead>

            <?php
			
			foreach ($usercheck_res['set'] AS $uck => $row):
				$userrights = unserializeBroken($row['rights']);
				$modicon = false;
				$userprops = "";
                
                if ($row['rights']!="") {
					$systemrights = array();
                	if (count($userrights)>0) {
                        $rightdesc = array('siteprops' => returnIntLang('usermanagement siteprops', false), 'sitestructure' => 'sitestructure', 'design' => returnIntLang('usermanagement design', false), 'contents' => 'contents', 'publisher' => 'publisher', 'imagesfolder' => 'images', 'downloadfolder' => 'documents', 'mediafolder' => 'media');
                        $moddesc = doSQL("SELECT `guid`, `description` FROM `wsprights`");
                        if ($moddesc['num']>0): foreach($moddesc['set'] AS $mdk => $mdv): $rightdesc[$mdv['guid']] = 'MOD: '.$mdv['description']; endforeach; endif;
                        $plgdesc = doSQL("SELECT `guid`, `pluginname` FROM `wspplugins`");
                        if ($plgdesc['num']>0): foreach($plgdesc['set'] AS $pdk => $pdv): $rightdesc[$pdv['guid']] = 'PLUGIN: '.$pdv['pluginname']; endforeach; endif;
                        foreach ($userrights as $key => $value) {
                            if (isset($rightdesc[$key])) {
                                if ($value==1):
                                    $userprops .= "<span class='btn btn-xs btn-primary'>".strtoupper($rightdesc[$key])."</span> ";
                                elseif ($value>3 && $value<10):
                                    $userprops .= "<span class='btn btn-xs btn-warning'>".strtoupper($rightdesc[$key])."</span> ";
                                elseif (strlen($value)>1 && intval($value)==0):
                                    $userprops .= "<span class='btn btn-xs btn-primary'>".strtoupper($rightdesc[$key])."</span> ";
                                endif;
                            }
                        }
                    }
				}
            
				if ($row["usertype"]==1):
					echo "<tr>";
					echo "<td class='col-md-1 singleline' nowrap='nowrap'><a href=\"#\" onClick=\"document.getElementById('edituser_".$row["rid"]."').submit();\" title=\"".setUTF8($row["realname"])."\">";
					echo setUTF8($row["realname"]);
					echo " [".setUTF8($row["user"])."]";
					echo "</a></td>";
					echo "<td>".$row["realmail"];
					echo "<form action=\"useredit.php\" method=\"post\" id=\"edituser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"userrid\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"predefined\" value=\"\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "<td>".$userprops."</td>";
					echo "<td>";
					echo "<a href=\"#\" onClick=\"document.getElementById('edituser_".$row["rid"]."').submit();\" title=\"".setUTF8($row["realname"])."\"><i class='far fa-btn fa-pencil-alt'></i></a> ";
                    $adminlogin_sql = "SELECT `sid` FROM `security` WHERE `userid` = ".intval($row['rid']);
					$adminlogin_res = doResultSQL($adminlogin_sql);
					if ($adminlogin_res===false) {
						echo "<a title='' href=\"#\" onClick=\"document.getElementById('removeadmin_".$row["rid"]."').submit();\"><i class='fas fa-btn fa-user-tie btn-success'></i></a> ";
					}
                    else {
                        echo "<i class='fas fa-btn fa-user-tie btn-success btn-disabled'></i> ";
                    }
					echo "<a href=\"#\" onClick=\"document.getElementById('userhistory_".$row["rid"]."').submit();\"><i class=\"fa fa-btn fa-calendar\"></i></a>";
					if ($adminlogin_res==false):
						echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"removeadmin_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
						echo "<input type=\"hidden\" name=\"id\" value=\"".$row['rid']."\">\n";
						echo "<input type=\"hidden\" name=\"op\" value=\"au\">\n";
						echo "</form>\n";
					endif;
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"userhistory_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"hy\">\n";
					echo "</form>\n";
					echo "</td>";
					echo "</tr>";
				elseif ($row["usertype"]==22):
					echo "<tr>";
					echo "<td><a href=\"#\" onClick=\"document.getElementById('edituser_".$row["rid"]."').submit();\" title=\"".setUTF8($row["realname"])."\">";
					echo setUTF8($row["realname"])."";
					echo "</a></td>";
					echo "<td>-";
					echo "<form action=\"useredit.php\" method=\"post\" id=\"edituser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"userrid\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"predefined\" value=\"\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "<td><span class='btn btn-xs btn-success'>WEBUSER</span></td>\n";
					echo "<td nowrap='nowrap'>\n";
                    echo "<a href=\"#\" onClick=\"document.getElementById('edituser_".$row["rid"]."').submit();\" title=\"".setUTF8($row["realname"])."\"><i class='far fa-btn fa-pencil-alt'></i></a> ";
					echo "<a href=\"#\" onClick=\"checkUserDel('deluser_".$row['rid']."','".$row['realname']."');\"><i class='fas fa-btn fa-user btn-danger'></i></a> ";
                    echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"deluser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"usevar\" value=\"".$_SESSION['wspvars']['usevar']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"ud\">\n";
					echo "<input type=\"hidden\" name=\"user_exist\" value=\"".$row['rid']."\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "</tr>";
				else:
					echo "<tr>";
					echo "<td nowrap='nowrap'><a href=\"#\" onClick=\"document.getElementById('edituser_".$row["rid"]."').submit();\" title=\"".setUTF8($row["realname"])."\">";
					if ($row['usertype']==0): echo "<span style=\"text-decoration: line-through\">"; endif;
					echo setUTF8($row["realname"])." [".setUTF8($row["user"])."]";
					if ($row['usertype']==0): echo "</span>"; endif;
					echo "</a></td>";
					echo "<td>";
					if ($row['usertype']==0): echo "<span style=\"text-decoration: line-through\">"; endif;
					echo $row["realmail"];
					if ($row['usertype']==0): echo "</span>"; endif;
					echo "<form action=\"useredit.php\" method=\"post\" id=\"edituser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"userrid\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"predefined\" value=\"\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "<td>";
					if ($row['usertype']!=0): echo $userprops; endif;
					echo "</td>\n";
					echo "<td nowrap='nowrap'>\n";
					echo "<a href=\"#\" onClick=\"document.getElementById('edituser_".$row["rid"]."').submit();\" title=\"".setUTF8($row["realname"])."\"><i class='far fa-btn fa-pencil-alt'></i></a> ";
                    if ($row['usertype']!=0):
						echo "<a href=\"#\" onClick=\"document.getElementById('makeadmin_".$row["rid"]."').submit();\"><i class='fas fa-btn fa-user'></i></a> ";
					elseif ($row['usertype']==0):
						echo "<a href=\"#\" onClick=\"checkUserDel('deluser_".$row['rid']."','".$row['realname']."');\"><i class='fas fa-btn fa-user btn-danger'></i></a> ";
                    endif;
                    echo "<a href=\"#\" onClick=\"document.getElementById('userhistory_".$row["rid"]."').submit();\"><i class=\"fa fa-btn fa-calendar\"></i></a> ";
					if ($row['usertype']!=0):
						echo "<a href=\"#\" onClick=\"checkUserInactive('inactiveuser_".$row['rid']."','".$row['realname']."');\" class=''><i class='fa fa-btn fa-ban btn-danger'></i></a> ";
					elseif ($row['usertype']==0):
						echo "<a href=\"#\" onClick=\"checkUserActive('activeuser_".$row['rid']."','".$row['realname']."');\"><i class='fa fa-btn fa-check-circle btn-success'></i></a> ";
                    endif;
					
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"deluser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"usevar\" value=\"".$_SESSION['wspvars']['usevar']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"ud\">\n";
					echo "<input type=\"hidden\" name=\"user_exist\" value=\"".$row['rid']."\">\n";
					echo "</form>\n";
					
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"activeuser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"uw\">\n";
					echo "</form>\n";
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"inactiveuser_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"us\">\n";
					echo "</form>\n";
					
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"makeadmin_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"ua\">\n";
					echo "</form>\n";
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"userhistory_".$row['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$row['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"hy\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "</tr>";
				endif;
			endforeach;
			?>
            
            
        </table>
    </div>
</div>