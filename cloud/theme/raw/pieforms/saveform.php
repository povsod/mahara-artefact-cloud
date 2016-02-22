<?php
echo $form_tag;
?>

	<div id="saveform_folderid_container" class="required select form-group">
		<?php echo $elements['folderid']['labelhtml']; ?>
		<span class="picker">
		<div class="input-group">
			<span class="input-group-addon" id="icon-addon">
				<span class="icon icon-folder-open icon-lg"></span>
			</span>
			<?php
				$html = $elements['folderid']['html'];
				$html = str_replace('<span class="picker">', '', $html);
				$html = str_replace('</span>', '', $html);
				echo $html;
			?>
		</div>
		</span>
	</div>
	<div id="saveform_submit_container" class="submitcancel form-group">
		<?php echo $elements['submit']['html']; ?>
	</div>

	<?php echo $hidden_elements; ?>

</form>
