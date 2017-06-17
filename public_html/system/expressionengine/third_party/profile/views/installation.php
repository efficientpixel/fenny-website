<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<?php if (count($templates_installed)) : ?>
	<h4><?=lang('installed')?>:</h4>
	<ul class="bullets">
		<?php foreach ($templates_installed as $installed) : ?>
			<li><?=$installed?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php if (count($template_errors)) : ?>
	<h4><?=lang('errors')?>:</h4>
	<ul class="bullets">
		<?php foreach ($template_errors as $error) : ?>
			<li>
				<span class="alert"><?=$error?></span>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=profile'.AMP.'method=do_installation')?>
 	<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
		<caption><?=lang('install_channels_header')?></caption>
		<tbody>
			<?php if (count($install_channels)) : ?>
				<tr class="<?=alternator('odd', 'even')?>">
					<td>
						<label style="height:100%;"><?=lang('channel')?></label>
					</td>
					<td style='width:50%;'>
						<ul>
						<?php foreach ($install_channels as $index => $name): ?>
							<li>
								<label class="radio">
									<input type="checkbox" checked="checked" name="templates[]" class="templates" value="<?=$index?>" />
									<?=$name?>
								</label>
								<?php if (isset($fields[$index])) : ?>
								<ul class="bullets">
								<?php foreach ($fields[$index] as $field) : ?>
									<li><?=$field?></li>
								<?php endforeach;?>
								</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			<?php endif; ?>
			<?php if (count($install_template_groups)) : ?>
				<tr class="<?=alternator('odd', 'even')?>">
					<td>
						<label style="height:100%;"><?=lang('template_group')?></label>
					</td>
					<td style='width:50%;'>
						<ul>
						<?php foreach ($install_template_groups as $index => $name): ?>
							<li>
								<label class="radio">
									<input type="checkbox" checked="checked" name="templates[]" class="templates" value="<?=$index?>" />
									<?=$name?>
								</label>
								<?php if (isset($templates[$index])) : ?>
								<ul class="bullets">
								<?php foreach ($templates[$index] as $template) : ?>
									<li><?=$template?></li>
								<?php endforeach;?>
								</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
						</ul>
					</td>
				</tr>	
			<?php endif; ?>
		</tbody>	
	</table>
	<?=form_submit(array('value' => 'Install', 'class' => 'submit'))?>
	<?=form_close()?>