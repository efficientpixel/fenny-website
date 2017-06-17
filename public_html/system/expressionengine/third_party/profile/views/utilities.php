<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
	<caption><?=lang('utilities')?></caption>
	<tbody>
		<tr class="<?=alternator('odd', 'even')?>">
			<td><?=lang('create_profiles')?></td>
			<td style='width:50%;'>
				<p><button id="create_profiles" class="submit"><?=lang('start')?></button></p>
				<p><div id="create_profiles_progressbar" class="progressbar"></div></p>
			</td>
		</tr>
		<tr class="<?=alternator('odd', 'even')?>">
			<td><?=lang('associate_existing_entries')?></td>
			<td style='width:50%;'>
				<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
					<thead>
						<th colspan="2"><?=lang('settings')?></th>
					</thead>
					<tbody>
						<tr>
							<td></td>
							<td><?=lang('associate_members_with_entries')?></td>
						</tr>
						<tr>
							<td><?=lang('member_field')?></td>
							<td><?=form_dropdown('member_field', $member_fields_all, NULL)?></td>
						</tr>
						<tr>
							<td></td>
							<td><?=lang('is_equal_to')?></td>
						</tr>
						<tr>
							<td><?=lang('profile_field')?></td>
							<td><?=form_dropdown('profile_field', $profile_fields)?></td>
						</tr>
					</tbody>
				</table>
				<p><button id="associate_existing_entries" class="submit"><?=lang('start')?></button></p>
				<p><div id="associate_existing_entries_progressbar" class="progressbar"></div></p>
			</td>
		</tr>
		<tr class="<?=alternator('odd', 'even')?>">
			<td><?=lang('sync_member_fields')?></td>
			<td style='width:50%;'>
				<form id="member_fields">
				<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
					<thead>
						<th><?=lang('member_fields')?></th>
						<th><?=lang('profile_fields')?></th>
					</thead>
					<tbody>
						<?php foreach ($member_fields as $field_id => $field_name) : ?>
						<tr>
							<td><?=$field_name?></td>
							<td><?=form_dropdown($field_id, $profile_fields_with_blank)?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</form>
				<p><button id="sync_member_fields" class="submit"><?=lang('start')?></button></p>
				<p><div id="sync_member_fields_progressbar" class="progressbar"></div></p>
			</td>
		</tr>
	</tbody>
</table>