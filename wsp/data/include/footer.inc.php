<?php
/**
* @description wsp footer file
* @author stefan@covi.de
* @since 3.1
* @version GIT
*/

// remove loading entry so we have less entries in db and a loaded page is a good information ;)
if (isset($_SESSION['wspvars']['userid'])) {
    $sql = "DELETE FROM `securitylog` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `lastaction` = 'start loading' ORDER BY `lastchange` DESC LIMIT 1";
    doSQL($sql);
    // save user position to security log after loading page
    $sql = "INSERT INTO `securitylog` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `lastposition` = '".escapeSQL($_SESSION['wspvars']['fpos'])."', `lastaction` = 'end loading', `lastchange` = '".time()."'";
    doSQL($sql);
}

if (isset($_REQUEST['clearsqlmsg'])):
	unset($_SESSION['wspvars']['showsql']);
endif;

if (isset($_SESSION['wspvars']['showsql']) && $_SESSION['wspvars']['showsql']===true):
	?>
	<fieldset id="sqlmsg" style="margin: 0px auto; width: 95%; max-width: 1326px;">
		<legend>SQL Statements <?php echo legendOpenerCloser('sqlmsgshow'); ?></legend>
		<div id="sqlmsgshow">
		<?php
		
		print_r($_SESSION['wspvars']['showsql']);
		
		if (isset($_SESSION['wspvars']['showsql']) && is_array($_SESSION['wspvars']['showsql'])):
			foreach($_SESSION['wspvars']['showsql'] AS $key => $value):
				echo "<p>".date("Y-m-d H:i:s", $value['time'])."<br />".$value['page']."<br /><em>".$value['sql']."</em></p>";
			endforeach;
		endif;
		
		?>
		<a href="<?php echo $_SERVER['PHP_SELF'];  ?>?clearsqlmsg">Clear SQL-Messages</a>
		</div>
	</fieldset>
	<?php
endif;

?>

<input type="hidden" id="cfc" value="0" />
<div id="footer">
	<p><strong><a href="http://www.websitepreview.de" target="_blank">WSP</a></strong> &copy; 2001 - 2026</p>
</div>

<script type="text/javascript">

$(document).ready(function() {
	
$('.datatable').each(function () {
		if (!$.fn.DataTable.isDataTable(this)) {
			$(this).DataTable({
				language: {
					url: './assets/js/dataTables.de-DE.json'
				}
			});
		}
	});

	$('select.select2').each(function () {
		if (!$(this).hasClass('select2-hidden-accessible')) {
			$('select.select2').select2({
				matcher: function (params, data) {
					const term = $.trim(params.term).toLowerCase();
					if (!term) { return data; }
					const text = (data.text || '').toLowerCase();
					const value = (data.id || '').toLowerCase();
					return text.includes(term) || value.includes(term) ? data : null;
				}
			});
		}
	});

});

<?php 
$queue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = 0 GROUP BY `param`, `id`";
$queue_res = doSQL($queue_sql);
$queue_num = intval($queue_res['num']);

if($_SESSION['wspvars']['mgroup']==7 && intval($queue_num)>0): ?>
function callBackgroundPublish() {
	backgroundPublish();
	setTimeout("callBackgroundPublish();", 10000);
	}
<?php elseif (intval($queue_num)>0): ?>
function callBackgroundPublish() {
	backgroundPublish();
	setTimeout("callBackgroundPublish();", 60000);
	}
<?php else: ?>
function callBackgroundPublish() {}
<?php endif; ?>

callBackgroundPublish();

//******* function scrolling to page area *******//

function scrollToAnchor(aid){
    var aTag = $("a[name='"+ aid +"']");
    var scrollFrom = $('#topholder').offset().top;
    var scrollTo = (aTag.offset().top-$('#topholder').height()-10);
    if (scrollTo<scrollFrom) {
	    $('html,body').animate({scrollTop: scrollTo},500);
	    }
	}

//******* function defining tables and converting it for mobile view *******//
function createFloatingTable() {
	$("td.tablecell.two").each(function(){ $(this).attr('colspan', 2); });
	$("td.tablecell.three").each(function(){ $(this).attr('colspan', 3); });
	$("td.tablecell.four").each(function(){ $(this).attr('colspan', 4); });
	$("td.tablecell.five").each(function(){ $(this).attr('colspan', 5); });
	$("td.tablecell.six").each(function(){ $(this).attr('colspan', 6); });
	$("td.tablecell.seven").each(function(){ $(this).attr('colspan', 7); });
	$("td.tablecell.eight").each(function(){ $(this).attr('colspan', 8); });
	
	var ua = window.navigator.userAgent;
	var msie = ua.indexOf("MSIE ");
	if (msie > 0) { var mobile = false; }
	else { var isMobile = window.matchMedia("only screen and (max-width: 760px)"); if (isMobile.matches) { var mobile = true; } }
	
	if (mobile) {
		$("table.tablelist").each(function(index){
			var ul = $("<ul>");
			ul.attr('class', $(this).attr('class'));
			ul.attr('id', 'tablelist-'+index);
			$(this).attr('id', 'listtable-'+index);
			$(this).find('td').each (function() {
				cc = $(this).attr('class');
				li = $("<li>");
				li.attr('class', cc);
				li.html(this.innerHTML);
				ul.append(li);
				});
			$(this).replaceWith(ul);
			});
		}
	}

//******* function extending autocomplete widget *******//
(function( $ ) {
	$.widget( "custom.combobox", {
		_create: function() {
			this.wrapper = $( "<span>" )
				.addClass( "custom-combobox" )
				.addClass( this.element.attr('class') )
				.insertAfter( this.element );
			var selname = this.element.attr('name');
			var selid = this.element.attr('id');
			this.element.hide();
			this._createAutocomplete(selname,selid);
			this._createShowAllButton();
			},
		
		_createAutocomplete: function(selname,selid) {
			var selected = this.element.children( ":selected" ),
			value = selected.val() ? selected.text() : "";
			
			if(value=="") {
				selected = this.element.children("optgroup").children( ":selected" ),
				value = selected.val() ? selected.text() : "";
				}
				
			this.input = $( "<input>" )
				.appendTo(this.wrapper)
				.val(value)
				.attr("title","")
				.addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
				.autocomplete({
					delay: 0,
					minLength: 0,
					source: $.proxy( this, "_source" )
					})
				.tooltip({
					tooltipClass: "ui-state-highlight"
					});
 			
			this._on( this.input, {
				autocompleteselect: function( event, ui ) {
					ui.item.option.selected = true;
					this._trigger( "select", event, {
						item: ui.item.option
						});
					if(ui.item.option.text!="") {
						var srcbase = $('#'+selid+'_preview').attr('srcbase');
						if (srcbase===undefined) { srcbase = ''; }
						$('#'+selid+'_preview').attr('src', srcbase + ui.item.option.value);
						}
					else {
						$('#'+selid+'_preview').attr('src', "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/media/screen/no.gif");
						}
					},
				autocompletechange: "_removeIfInvalid"
				});
			},
			
		_createShowAllButton: function() {
			var input = this.input,
			wasOpen = false;
			
			$("<a>")
			.attr( "tabIndex", -1 )
			.tooltip()
			.appendTo( this.wrapper )
			.button({
				icons: {
					primary: "ui-icon-triangle-1-s"
					},
				text: false
				})
			.removeClass( "ui-corner-all" )
			.addClass( "custom-combobox-toggle ui-corner-right" )
			.mousedown(function() {
				wasOpen = input.autocomplete( "widget" ).is( ":visible" );
				})
			.click(function() {
				input.focus();
				// Close if already visible
				if ( wasOpen ) {
					return;
					}
				// Pass empty string as value to search for, displaying all results
				input.autocomplete( "search", "" );
				});
			},

		_source: function( request, response ) {
			var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
			response( this.element.children( "optgroup" ).children( "option" ).map(function() {
				var text = $(this).text();
				if ( this.value && ( !request.term || matcher.test(text) || matcher.test(this.value) ) ) {
					return {
						label: text,
						value: text,
						option: this,
						category: $(this).closest("optgroup").attr("label")
						};
					}
				}));
			},
		
		_removeIfInvalid: function( event, ui ) {
			// Selected an item, nothing to do
			if ( ui.item || this.input.val()=="" ) {
				if (this.input.val()=="" ) {
					this.element.val( "" );
					$('#'+this.element.attr('id')+'_preview').attr('src', "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/media/screen/no.gif");
					}
				return;
				}
			
			// Search for a match (case-insensitive)
			var value = this.input.val(),
				valueLowerCase = value.toLowerCase(),
				valid = false;
			this.element.children( "option" ).each(function() {
				if ( $( this ).text().toLowerCase() === valueLowerCase ) {
					this.selected = valid = true;
					return false;
					}
        		});

			// Found a match, nothing to do
			if ( valid ) { return; }
			
			// found an external link, nothing to do (if the next step deals with it)
			if ( value.includes('http') ) { return; }
			
			// Remove invalid value
			this.input.val("").attr( "title", value + " wurde nicht gefunden" ).tooltip( "open" );
			this.element.val( "" );
			this._delay(function() {
				this.input.tooltip( "close" ).attr( "title", "" );
				}, 2500 );
			this.input.autocomplete( "instance" ).term = "";
			},
			
		_destroy: function() {
			this.wrapper.remove();
			this.element.show();
			}
		});
	})( jQuery );
//******* Ende Funtionen für Dropbox-Autocomplete *******//
 
$(document).ready(function() {      
	
	/* run function to style tables */
	createFloatingTable();
	
	/* passLiTable is deprecated since 6.0 */ 
	if ($(window).width()>480) {
		passLiTable('ul.tablelist', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
		}
	
	/* run function to init autocomplete */
	$(function() {
		$( ".autocombo" ).combobox();
		});

	});

<?php if (key_exists('preventleave', $_SESSION['wspvars']) && $_SESSION['wspvars']['preventleave']): ?>
$(window).bind('beforeunload', function () {
	if (document.getElementById('cfc').value==1) {
		if (confirm ('<?php echo returnIntLang('request leave page without saving', false); ?>')) {
			return true;
			}
		else {
			return false;
			}
		}
	});
<?php endif; ?>

</script>
<?php

if (isset($_SESSION['wspvars']['devstat']) && $_SESSION['wspvars']['devstat']===true):
    $mgu = memory_get_usage(); $size = array('B','KB','MB','GB'); $m = 0;
    while ($mgu>1024) { $mgu = $mgu/1024; $m++; }
    echo "<p style='padding: 0px 1%; color: darkorange;'>RAM ".round($mgu,2)." ".$size[$m]."</p>";
    $mgu = memory_get_peak_usage(); $size = array('B','KB','MB','GB'); $m = 0;
    while ($mgu>1024) { $mgu = $mgu/1024; $m++; }
    echo "<p style='padding: 0px 1%; color: red;'>MAX RAM ".round($mgu,2)." ".$size[$m]."</p>";
endif;

?>
</body>
</html>
<!-- EOF -->