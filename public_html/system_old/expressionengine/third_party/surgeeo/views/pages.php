<div id="surgeeo_pages">
	<h3>Enter in SEO data for specific URLs</h3>
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=surgeeo'.AMP.'method=update_pages')?>
	<?php if ($pages === FALSE) : ?>
	<?php 
	/**
	*	If no pages yet
	*/
	?>
		<h4>No Page Data Added Yet!</h4>
		<dl class="surgeeo_page clearfix collapsed add" data-job="add">
			<dt><span>Add Page Data...</span></dt>
		</dl>
		<script> var SURGEEO_KEY = 0; </script>
	<?php else: ?>
	<?php 
	/**
	*	If pages exist
	*/
	?>
		<?php foreach($pages as $key => $page) : ?>
	<dl class="surgeeo_page clearfix collapsed">
		<dt><span><?php echo $page->uri ?></span></dt>
		<dd>
			<ul>
				<li class="clearfix top even">
					<div class="clearfix">
						<label for="page_<?php echo $key; ?>_uri">Url: </label>
						<input type="text" name="pages[<?php echo $key; ?>][uri]" id="page_<?php echo $key; ?>_uri" value="<?php echo $page->uri ?>" placeholder="url" />
					</div>
				</li>
				<li class="clearfix">
					<label for="page_<?php echo $key; ?>_title">Title: </label>
					<input type="text" name="pages[<?php echo $key; ?>][title]" id="page_<?php echo $key; ?>_title" value="<?php echo $page->title ?>" placeholder="title" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_keywords">Keywords: </label>
					<input type="text" name="pages[<?php echo $key; ?>][keywords]" id="page_<?php echo $key; ?>_keywords" value="<?php echo $page->keywords ?>" placeholder="keywords" />
				</li>
				<li class="clearfix">
					<label for="page_<?php echo $key; ?>_description">Description: </label>
					<textarea name="pages[<?php echo $key; ?>][description]" id="page_<?php echo $key; ?>_description" placeholder="description"><?php echo $page->description ?></textarea>
					<input type="hidden" name="pages[<?php echo $key; ?>][uri_id]" value="<?php echo $page->uri_id; ?>" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_author">Author: </label>
					<input type="text" name="pages[<?php echo $key; ?>][author]" id="page_<?php echo $key; ?>_author" value="<?php echo $page->author ?>" placeholder="Name, Email" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_gplus">Google+ URL: </label>
					<input type="text" name="pages[<?php echo $key; ?>][gplus]" id="page_<?php echo $key; ?>_gplus" value="<?php echo $page->gplus ?>" placeholder="Google+ Profile URL" />
				</li>
				<!-- Open Graph -->
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_og_url">OpenGraph URL: </label>
					<input type="text" name="pages[<?php echo $key; ?>][og_url]" id="page_<?php echo $key; ?>_og_url" value="<?php echo $page->og_url ?>" placeholder="Open Graph URL" />
				</li>
				<li class="clearfix">
					<label for="page_<?php echo $key; ?>_og_description">OpenGraph Description: </label>
					<textarea name="pages[<?php echo $key; ?>][og_description]" id="page_<?php echo $key; ?>_og_description" placeholder="Open Graph Description"><?php echo $page->og_description;?></textarea>
					<input type="hidden" name="pages[<?php echo $key; ?>][uri_id]" value="<?php echo $page->uri_id; ?>" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_og_img">OpenGraph Image: </label>
					<input type="text" name="pages[<?php echo $key; ?>][og_img]" id="page_<?php echo $key; ?>_og_img" value="<?php echo $page->og_img ?>" placeholder="Open Graph Image URL" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_og_type">OpenGraph Media Type: </label>
					<input type="text" name="pages[<?php echo $key; ?>][og_type]" id="page_<?php echo $key; ?>_og_type" value="<?php echo $page->og_type ?>" placeholder="Open Graph Media Type" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_twtr_title">Twitter Title: </label>
					<input type="text" name="pages[<?php echo $key; ?>][twtr_title]" id="page_<?php echo $key; ?>_twtr_title" value="<?php echo $page->twtr_title ?>" placeholder="Twitter Card Title" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_twtr_type">Twitter Type: </label>
					<input type="text" name="pages[<?php echo $key; ?>][twtr_type]" id="page_<?php echo $key; ?>_twtr_type" value="<?php echo $page->twtr_type ?>" placeholder="Twitter Card Type" />
				</li>
				<li class="clearfix even">
					<label for="page_<?php echo $key; ?>_twtr_img">Twitter Image: </label>
					<input type="text" name="pages[<?php echo $key; ?>][twtr_img]" id="page_<?php echo $key; ?>_twtr_img" value="<?php echo $page->twtr_img ?>" placeholder="Twitter Card Image" />
				</li>
				<li class="clearfix">
					<label for="page_<?php echo $key; ?>_twtr_description">Twitter Description: </label>
					<textarea name="pages[<?php echo $key; ?>][twtr_description]" id="page_<?php echo $key; ?>_twtr_description" placeholder="Twitter Card Description"><?php echo $page->twtr_description;?></textarea>
					<input type="hidden" name="pages[<?php echo $key; ?>][uri_id]" value="<?php echo $page->uri_id; ?>" />
				</li>

			</ul>
		</dd>
		<dd class="clearfix deleter"><a data-role="delete" data-id="<?php echo $page->uri_id; ?>" href="<?php echo $ajax_url; ?>&page_id=<?php echo $page->uri_id; ?>">Delete</a></dd>
	</dl>
		<?php if(($key+1) === count($pages)) : ?>
		<script> var SURGEEO_KEY = <?php echo $key; ?> </script>
		<?php endif; ?>
		<?php endforeach; ?>
	<dl class="surgeeo_page clearfix collapsed add" data-job="add">
		<dt><span>Add Another...</span></dt>
	</dl>
	<?php endif; ?>
	<p><?=form_submit('submit_pages', lang('submit'), 'class="submit"')?></p>
	<?=form_close()?>
</div>