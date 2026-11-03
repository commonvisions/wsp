<?php

$interpreterClass = "imgbox";

if (!class_exists("imgbox")) {
	class imgbox extends clsInterpreter {
		var $title = "ImgBox";
		var $parsefile = "imgbox.php";
		var $version = "6.26.11";
		var $guid = "71086f2b-974b-4665-8576-54a796598a5f";
		var $multilang = [
            'de' => [
                '71086f2b-974b-4665-8576-54a796598a5f contents' => 'Inhalte',
                '71086f2b-974b-4665-8576-54a796598a5f content desc' => 'Beschreibung',
            ]
        ];
		var $flexible = true; 
        
        function init() {}

		// get content title
		function getView($value, $mid, $cid) {
			$clickmode = ['', 'Link', 'Download', 'Image'];
            $desc = trim(stripslashes(stripslashes(stripslashes(substr(setUTF8($value['desc'] ?? ''), 0, 100)))));
            $desc = trim((string) $desc ?: (getImageDesc(trim($value['image'] ?? '')) ?: ''));
            return $desc . ($desc ? ' » ' : '') . ($clickmode[intval($value['clickmode'] ?? 0)] ?? '');
        }	// getView()

		// edit contents
		function getEdit($value, $mid, $cid) {
			ob_start();
			?>
			<script language="javascript" type="text/javascript">
			<!--
			function submitForm() {
				}	// submitForm()
			//-->
			</script>

			<div class="card">
				<div class="card-header"><?php echo returnIntLang('71086f2b-974b-4665-8576-54a796598a5f contents'); ?></div>
				<div id="fieldset_content" class="card-body">
                    <div class="row">
                        <div class="col col-3"><?php echo returnIntLang('71086f2b-974b-4665-8576-54a796598a5f content desc'); ?></div>
                        <div class="col col-9"><input type="text" name="field[desc]" id="field_desc" value="<?php echo setUTF8(stripslashes($value['desc'] ?? '')); ?>" class="form-control" /></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col col-3">(Vorschau-)Bild</div>
                        <div class="col col-3"><select name="field[image]" id="field_image" class="form-control" size="1">
							<option value="">Bitte ausw&auml;hlen</option>
							<?php echo imageSelect('', '', true, array($value['image'] ?? ''), hideLast: true); ?>
						</select></div>
                        <div class="col col-3">Klick-Modus</div>
                        <div class="col col-3"><select name="field[clickmode]" id="field_clickmode" class="form-control" size="1">
							<option value="0">Bitte ausw&auml;hlen</option>
							<option value="1" <?php if (($value['clickmode'] ?? 0) == 1) echo " selected=\"selected\" "; ?>>Link (1+2)</option>
							<option value="2" <?php if (($value['clickmode'] ?? 0) == 2) echo " selected=\"selected\" "; ?>>Download (3)</option>
							<option value="3" <?php if (($value['clickmode'] ?? 0) == 3) echo " selected=\"selected\" "; ?>>Originalbild (4)</option>
						</select></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col col-3 offset-3"><label><input type="hidden" name="field[clearbeforeimage]" value="0"><input type="checkbox" name="field[clearbeforeimage]" value="1" <?php if (($value['clearbeforeimage'] ?? 0)==1) echo " checked=\"checked\" "; ?> /> Umbruch vor Bild erzwingen</label></div>
                        <div class="col col-3"><label><input type="hidden" name="field[clearafterimage]" value="0"><input type="checkbox" name="field[clearafterimage]" value="1" <?php if (($value['clearafterimage'] ?? 0)==1) echo " checked=\"checked\" "; ?> /> Umbruch nach Bild erzwingen</label></div>
                        <div class="col col-3"><label><input type="hidden" name="field[showemptyimage]" value="0"><input type="checkbox" name="field[showemptyimage]" value="1" <?php if (($value['showemptyimage'] ?? 0) ==1) echo " checked=\"checked\" "; ?> /> Box auch ohne Vorschau anzeigen</label></div>
                    </div>
                </div>
                <div class="card-header">Bitte wählen Sie eine der folgenden Optionen</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-3">(1) Link intern</div>
                        <div class="col-3">
                            <select name="field[internlink]" id="field_internlink" class="one full">
                                <option value="">Bitte auswählen</option>
                                <?php echo getMenuLevel(0, 0, 4, [$value['internlink'] ?? 0]); ?>
                            </select>
                        </div>
                        <div class="col-3">(2) Link extern</div>
                        <div class="col-3">
                            <input type="text" name="field[externlink]" id="field_externlink" value="<?php echo trim($value['externlink'] ?? ''); ?>" class="form-control" placeholder="https://..." />
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-3">(1+2) Linkziel</div>
                        <div class="col-3"><select name="field[linktarget]" id="field_linktarget" class="form-control" size="1">
                            <option value="_blank" <?php if (($value['linktarget'] ?? '') == "_blank") echo " selected=\"selected\" "; ?>>Neues Fenster</option>
                            <option value="_self" <?php if (($value['linktarget'] ?? '') == "_self") echo " selected=\"selected\" "; ?>>Gleiches Fenster</option>
                        </select></div>
                        <div class="col-3">(3) Downloaddatei</div>
                        <div class="col-3"><select name="field[download]" id="field_download" class="form-control" size="1">
                            <option value="">Bitte auswählen</option>
                            <?php echo getMediaDownload('/', [($value['download'] ?? '')]); ?>
                        </select></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-3">(4) Originalbild</div>
                        <div class="col-3"><select name="field[fullimage]" id="field_fullimage" class="form-control" size="1">
                            <option value="">Bitte ausw&auml;hlen</option>
                            <?php echo getImageFiles('/', array($value['fullimage'])); ?>
                        </select></div>
                        <div class="col-3">(4) öffnet als</div>
                        <div class="col-3"><select name="field[fullmode]" id="field_fullmode" class="form-control" size="1">
                            <option value="1" <?php if (($value['fullmode'] ?? '')==1) echo " selected=\"selected\" "; ?>>Popup</option>
                            <option value="0" <?php if (($value['fullmode'] ?? '')==0) echo " selected=\"selected\" "; ?>>Lightbox</option>
                        </select></div>
                    </div>
                </div>
                <div class="card-header">Bildbeschreibung</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col col-3">Position</div>
                        <div class="col col-3"><select name="field[contentpos]" id="field_contentpos" class="form-control" size="1">
                            <option value="1" <?php if ($value['contentpos']==1) echo " selected=\"selected\" "; ?>>nur als Titel</option>
                            <option value="0" <?php if ($value['contentpos']==0) echo " selected=\"selected\" "; ?>>unter dem Bild</option>
                            <option value="2" <?php if ($value['contentpos']==2) echo " selected=\"selected\" "; ?>>über dem Bild</option>
                        </select></div>
                        <div class="col col-3">Inhalt</div>
                        <div class="col col-3"><textarea name="field[content]" id="field_content" class="form-control"><?php echo stripslashes($value['content']); ?></textarea></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col col-3">Holder Klasse</div>
                        <div class="col col-3"><input type="text" name="field[holderclass]" id="field_holderclass" value="<?php echo htmlentities(stripslashes($value['holderclass'] ?? '')); ?>" class="form-control" /></div>
                        <div class="col col-3">Holder CSS</div>
                        <div class="col col-3"><input type="text" name="field[holdercss]" id="field_holdercss" value="<?php echo htmlentities(stripslashes($value['holdercss'] ?? '')); ?>" class="form-control" /></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col col-3">Rahmen Klasse</div>
                        <div class="col col-3"><input type="text" name="field[imgareaclass]" id="field_imgareaclass" value="<?php echo htmlentities(stripslashes($value['imgareaclass'] ?? '')); ?>" class="form-control" /></div>
                        <div class="col col-3">Rahmen CSS</div>
                        <div class="col col-3"><input type="text" name="field[imgareacss]" id="field_imgareacss" value="<?php echo htmlentities(stripslashes($value['imgareacss'] ?? '')); ?>" class="form-control" /></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col col-3">Bild Klasse</div>
                        <div class="col col-3"><input type="text" name="field[imgclass]" id="field_imgclass" value="<?php echo htmlentities(stripslashes($value['imgclass'] ?? '')); ?>" class="form-control" /></div>
                        <div class="col col-3">Bild CSS</div>
                        <div class="col col-3"><input type="text" name="field[imgcss]" id="field_imgcss" value="<?php echo htmlentities(stripslashes($value['imgcss'] ?? '')); ?>" class="form-control" /></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col col-3">Textbereich Klasse</div>
                        <div class="col col-3"><input type="text" name="field[textareaclass]" id="field_textareaclass" value="<?php echo htmlentities(stripslashes($value['textareaclass'] ?? '')); ?>" class="form-control" /></div>
                        <div class="col col-3">Textbereich CSS</div>
                        <div class="col col-3"><input type="text" name="field[textareacss]" id="field_textareacss" value="<?php echo htmlentities(stripslashes($value['textareacss'] ?? '')); ?>" class="form-control" /></div>
                    </div>
			    </div>
            </div>
			<?php
			
			$edit_output = ob_get_contents();
			ob_end_clean();

			return $edit_output;
			}	// getEdit()

		function getContent($value, $mid, $cid, $publishlang) {
			
			$value = unserializeBroken($value);

			$parsecontent = "";
						
			if (trim($value['image'] ?? '')!="" || (isset($value['showemptyimage']) && intval($value['showemptyimage'])==1)):
				
				$imageid = md5(trim($value['fullimage']).time().trim($value['image']).trim($value['content']));
				$title = prepareTextField($value['desc']);
				if (trim($title)==''): $title = prepareTextField(strip_tags($value['content'])); endif;
				
				if ($value['clearbeforeimage']==1):
					$parsecontent.= "<hr style=\"position: relative; clear: both; float: none; visibility: hidden; width: 100%; height: 0.1px; border: none; background: none;\" />";
				endif;
				
				if (trim($value['holdercss'])=='' && trim($value['holderclass'])==''):
					$value['holdercss'] = 'width: 100%; overflow: hidden;';
				endif;
				
				$parsecontent.= "<div id=\"\" class=\"imgboxholder ".$value['holderclass']."\" style=\"".$value['holdercss']."\">";
				if ($value['contentpos']==2):
					$parsecontent.= "<div id=\"textarea\" class=\"textareaclass ".$value['textareaclass']."\" style=\"".$value['textareacss']."\">";
					$parsecontent.= stripcslashes(stripcslashes(stripcslashes($value['content'])));
					$parsecontent.= "</div>";
					$parsecontent.= "<hr style=\"clear: both; float: none; visibility: hidden; width: 100%; height: 0.1px; border: none; background: none;\" />";
				endif;
				$parsecontent.= "<div id=\"imgarea\" class=\"imgholder ".$value['imgareaclass']."\" style=\"".$value['imgareacss']."\">";
				
				if (trim($value['image'])!=""):
					if (intval($value['clickmode'])>0):
						$parsecontent.= "<a href=\"";
						if (trim($value['internlink'])!=""):
							$parsecontent.= "intern:#".trim($value['internlink']); 
						elseif (trim($value['externlink'])!=""):
							$parsecontent.= trim($value['externlink']);
						elseif (trim($value['download'])!=""):
							$parsecontent.= str_replace("//", "/", str_replace("//", "/","/media/".trim($value['download'])));
						elseif (trim($value['fullimage'])!=""):
							$parsecontent.= str_replace("//", "/", str_replace("//", "/", "/media/images/".trim($value['fullimage'])));
						else:
							$parsecontent.= "#";
						endif;
						$parsecontent.= "\" ";
						if (intval($value['clickmode'])==1 && trim($value['linktarget'])!=""):
							$parsecontent.= " target=\"".trim($value['linktarget'])."\" ";
						elseif (intval($value['clickmode'])==2):
							$parsecontent.= " target=\"_blank\" ";
						elseif (intval($value['clickmode'])==3):
							$parsecontent.= " title=\"".$title."\" class=\"fancybox-".$imageid."\" rel=\"page\" ";
						endif;
						$parsecontent.= " >";
					endif;
					// IMAGE
					$parsecontent.= "<img src=\"".cleanPath("/media/images/", $value['image'] ?? '')."\" class=\"".$value['imgclass']."\" style=\"".$value['imgcss']."\" title=\"".$title."\" alt=\"".$title."\" />";
					// close link, if clickmode requires links
					if (intval($value['clickmode'])>0): $parsecontent.= "</a>"; endif;
				endif;
				
				$parsecontent.= "</div>";

				if ($value['contentpos']==0):
					$parsecontent.= "<div id=\"textarea\" class=\"textareaclass ".$value['textareaclass']."\" style=\"".$value['textareacss']."\">";
					if (trim($value['image'])=="" && intval($value['clickmode'])>0):
						$parsecontent.= "<a href=\"";
						if (trim($value['internlink'])!=""):
							$parsecontent.= "intern:#".trim($value['internlink']); 
						elseif (trim($value['externlink'])!=""):
							$parsecontent.= trim($value['externlink']);
						elseif (trim($value['download'])!=""):
							$parsecontent.= str_replace("//", "/", str_replace("//", "/","/media/".trim($value['download'])));
						elseif (trim($value['fullimage'])!=""):
							$parsecontent.= str_replace("//", "/", str_replace("//", "/", "/media/images/".trim($value['fullimage'])));
						else:
							$parsecontent.= "#";
						endif;
						$parsecontent.= "\" ";
						if (intval($value['clickmode'])==1 && trim($value['linktarget'])!=""):
							$parsecontent.= " target=\"".trim($value['linktarget'])."\" ";
						elseif (intval($value['clickmode'])==2):
							$parsecontent.= " target=\"_blank\" ";
						elseif (intval($value['clickmode'])==3):
							$parsecontent.= " title=\"".$title."\" class=\"fancybox-".$imageid."\" rel=\"page\" ";
						endif;
						$parsecontent.= " >";
					endif;
					$parsecontent.= nl2br(stripcslashes(stripcslashes(stripcslashes($value['content']))));
					if (trim($value['image'])=="" && intval($value['clickmode'])>0): $parsecontent.= "</a>"; endif;
					$parsecontent.= "</div>";
					$parsecontent.= "<hr style=\"clear: both; float: none; visibility: hidden; width: 100%; height: 0.1px; border: none; background: none;\" />";
				endif;
				$parsecontent.= "</div>";
				if (intval($value['clickmode'])==3):
					// add fancybox script for link class
					$parsecontent.= "<script type=\"text/javascript\">\n";
					$parsecontent.= "<!--\n";
					$parsecontent.= "\$(document).ready(function() { \$(\".fancybox-".$imageid."\").fancybox({}); });\n";
					$parsecontent.= "// -->\n";
					$parsecontent.= "</script>\n";
				endif;
				if ($value['clearafterimage']==1):
					$parsecontent.= "<hr style=\"position: relative; clear: both; float: none; visibility: hidden; width: 100%; height: 0.1px; border: none; background: none;\" />";
				endif;
				// find and replace link vars

				// YET MISSING

			endif;
			
			$parser = $parsecontent;
			return $parser;
		}
	}	// class imgbox
}	// if
?>