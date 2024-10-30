<div id="ltb_admin">

	<h1>Longtail Keyword Browser</h1>

	<?php if (get_option('ltb_sponsor_1')){ ?><div id="ltb_sponsor_1"><?php echo get_option('ltb_sponsor_1'); ?></div><?php } ?>

	<table id="ltb_layout_table" style="width: 99%;">
	<tr>
		<td width="99%">
			<div id="ltb_view_wrapper">
				<div id="ltb_view">
					<div style="margin: 15px;">
					<?php require($view_path); ?>
					</div>
				</div>
				<div id="ltb_spacer"></div>
				<div style="clear: both;"></div>
			</div>
		</td>
		<?php if (get_option('ltb_sponsor_2')){ ?><td>&nbsp;</td><td id="ltb_sponsor_1"><?php echo get_option('ltb_sponsor_2'); ?></td><?php } ?>
	</tr>
	</table>

</div>