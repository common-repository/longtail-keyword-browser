<?php

if (!isset($_POST['seed']))
{
	$_POST['seed'] = '';
}

if (!isset($_POST['country']))
{
	$_POST['country'] = 'us';
}

?>

<form action="admin.php?page=<?php echo $this->name ?>" method="post">
	<table>
		<tr>
			<th>Seed:</th>
			<td>
				<input type="text" name="seed" id="seed" value="<?php echo htmlspecialchars($_POST['seed']) ?>" size="45" />
			</td>
			<td>
				<select name="country" style="width: 75px;">
					<option value="us"<?php if ($_POST['country'] == 'us'){ echo ' selected'; } ?>>US</option>
					<option value="uk"<?php if ($_POST['country'] == 'uk'){ echo ' selected'; } ?>>UK</option>
				</select>
			</td>
			<td>
				<input type="submit" value="Browse" class="button" />
			</td>
		</tr>
	</table>
</form>

<div id="ltb_results"></div>

<script type="text/javascript">

jQuery('#ltb_results').delegate('a', 'click', function (e) {
	  e.preventDefault();
	  this.blur();
	  jQuery('#ltb_results').jstree('toggle_node', this);
}) 
	
jQuery('#ltb_results').jstree({
	plugins: ['themes', 'json_data'],
	core: {
		animation: 250
	},
	json_data: {
		ajax: {
			url: 'admin.php?page=<?php echo $this->name ?>&action=browse_node',
			data: function (n) {
				return { 
					keyword: n != -1 ? jQuery('#ltb_results').jstree('get_text', n) : '<?php echo addslashes($_POST['seed']) ?>',
					country: '<?php echo addslashes($_POST['country']) ?>'
				}; 
			},
			progressive_render: true,
			progressive_unload: true
		}
	},
	themes: {
		theme: 'classic',
		url: '<?php echo $this->base_url ?>/includes/js/jstree/themes/classic/style.css'
	}
});

</script>