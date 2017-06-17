<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<!-- begin right column -->
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=set_encryption_key')?>
	<p class="notice"><?=lang('set_encryption_key')?></p>

	<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
		<caption><?=lang('encryption_key')?></caption>
		<tbody>
			<tr class="even">
				<td>
					<label><?=lang('encryption_key')?></label>
					<div class="subtext"><?=lang('encryption_key_instructions')?></div>
 				</td>
				<td style="width:50%;">
					<input dir="ltr" type="text" name="encryption_key" id="encryption_key" value="" size="90" maxlength="128" />
				</td>
			</tr>
		</tbody>
	</table>

<p><input type="submit" name="submit" value="<?=lang('submit')?>" class="submit" /></p>
</form>

