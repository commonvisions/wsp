<?php
/**
 * msgheader
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-03-08
 */
?>
<script type="text/javascript">
			
function showInnerMsg(msgtype) {
	$.post("xajax/ajax.showinnermsg.php", { 'msgtype': msgtype })
		.done (function(data) {
			if (data) {
				$('#'+msgtype).html(data);
				$('#'+msgtype).toggle('blind');
				}
			})
	}

</script>
<div id="infoholder">
<fieldset id="noticemsg" style="display: none;"></fieldset>
<fieldset id="errormsg" style="display: none;"></fieldset>
<fieldset id="resultmsg" style="display: none;"></fieldset>
</div>
<?php // EOF ?>