$(function() {
	function SurgeTmpl() {
		 var _tmpl = '<dl class="surgeeo_page clearfix">\
				<dt><span></span></dt>\
				<dd>\
					<ul>\
						<li class="clearfix top even">\
							<div class="clearfix">\
								<label for="page_{index}_uri">Url: </label>\
								<input type="text" name="pages[{index}][uri]" id="page_{index}_uri" value="" placeholder="url" />\
							</div>\
						</li>\
						<li class="clearfix">\
							<label for="page_{index}_title">Title: </label>\
							<input type="text" name="pages[{index}][title]" id="page_{index}_title" value="" placeholder="title" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_keywords">Keywords: </label>\
							<input type="text" name="pages[{index}][keywords]" id="page_{index}_keywords" value="" placeholder="keywords" />\
						</li>\
						<li class="clearfix">\
							<label for="page_{index}_description">Description: </label>\
							<textarea name="pages[{index}][description]" id="page_{index}_description" placeholder="description"></textarea>\
							<input type="hidden" name="pages[{index}][uri_id]" value="" />\
						</li>\
						<li class="clearfix">\
							<label for="page_{index}_author">Author: </label>\
							<input type="text" name="pages[{index}][author]" id="page_{index}_title" value="" placeholder="Name, Email" />\
						</li>\
						<li class="clearfix">\
							<label for="page_{index}_gplus">Google+ URL: </label>\
							<input type="text" name="pages[{index}][gplus]" id="page_{index}_title" value="" placeholder="Google+ Profile URL" />\
						</li>\
						<!-- Open Graph -->\
						<li class="clearfix even">\
							<label for="page_{index}_og_description">Open Graph - Description: </label>\
							<input type="text" name="pages[{index}][og_description]" id="page_{index}_og_description" value="" placeholder="Open Graph Description" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_og_url">Open Graph - URL: </label>\
							<input type="text" name="pages[{index}][og_url]" id="page_{index}_og_url" value="" placeholder="Open Graph URL" />\
						</li>\
						<li class="clearfix">\
							<label for="page_{index}_og_description">Open Graph - Description: </label>\
							<textarea name="pages[{index}][og_description]" id="page_{index}_og_description" placeholder="Open Graph Description"></textarea>\
							<input type="hidden" name="pages[{index}][uri_id]" value="pages[{index}][uri_id]" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_og_img">Open Graph - Image: </label>\
							<input type="text" name="pages[{index}][og_img]" id="page_{index}_og_img" value="" placeholder="Open Graph Image URL" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_og_type">Open Graph - Media Type: </label>\
							<input type="text" name="pages[{index}][og_type]" id="page_{index}_og_type" value="" placeholder="Open Graph Media Type" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_twtr_title">Twitter Card - Title: </label>\
							<input type="text" name="pages[{index}][twtr_title]" id="page_{index}_twtr_title" value="" placeholder="Twitter Card Title" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_twtr_type">Twitter Card - Type: </label>\
							<input type="text" name="pages[{index}][twtr_type]" id="page_{index}_twtr_type" value="" placeholder="Twitter Card Type" />\
						</li>\
						<li class="clearfix even">\
							<label for="page_{index}_twtr_img">Twitter Card - Image: </label>\
							<input type="text" name="pages[{index}][twtr_img]" id="page_{index}_twtr_img" value="" placeholder="Twitter Card Image" />\
						</li>\
						<li class="clearfix">\
							<label for="page_{index}_twtr_description">Twitter Card - Description: </label>\
							<textarea name="pages[{index}][twtr_description]" id="page_{index}_twtr_description" placeholder="Twitter Card Image"></textarea>\
							<input type="hidden" name="pages[{index}][uri_id]" value="pages[{index}][uri_id]" />\
						</li>\
					</ul>\
				</dd>\
				<dd class="clearfix deleter"><a data-role="delete" data-id="" href="#">Delete</a></dd>\
			</dl>';

			this.parse = function(replace) {
				return _tmpl.replace(/{index}/gi, replace);
			};
	}
	var SurgeEO = (function() {

		var _key = 0,
			_adder = $('.surgeeo_page.add');

		var _addNew = function() {
			_key ++;
			var tmpl = new SurgeTmpl(),
				tmSt = tmpl.parse(_key),
				$tmpl = $(tmSt);
			_adder.before($tmpl);
		}

		var init = function() {
			_key = SURGEEO_KEY;

			if (jQuery.fn.on) {  /** version 1.7 and up **/
				//Create new
				$('#surgeeo_pages').on('click', '.surgeeo_page dt', function(e) {
					e.preventDefault();

					$this = $(this).parents('dl');

					if($this.data('job') === 'add') {
						_addNew();
						return;
					};
					
					if( $this.hasClass('collapsed') ) {
						$this.removeClass('collapsed');
					} else {
						$this.find('dt span').text($this.find('li.top input').val());
						$this.addClass('collapsed');
					}
				});

				//Delete current or new
				$('#surgeeo_pages').on('click', '.surgeeo_page .deleter a', function(e) {
					e.preventDefault();

					var conf = confirm("Are you sure you want to delete this page data?");
					if(conf === false) {
						return false;
					}

					$this = $(this);

					if($this.data('id') === '' || $this.data('id') === false) {
						$this.parents('dl').remove();
						return false;
					}

					$.getJSON($this.attr('href'), {}, function(data) {
					    if(typeof data.status !== 'undefined' && data.status === 'success') {
					    	$this.parents('dl').remove();
					    }
					});
				});
			} else if (jQuery.fn.delegate) { /** versions since 1.4.3 **/
				//Create new
                $('#surgeeo_pages').delegate('.surgeeo_page dt', 'click', function(e) {
                    e.preventDefault();

                    $this = $(this).parent();

                    if($this.attr('data-job') == 'add') {
                        _addNew();
                        return;
                    };
                    
                    if( $this.hasClass('collapsed') ) {
                        $this.removeClass('collapsed');
                    } else {
                        $this.find('dt span').text($this.find('li.top input').val());
                        $this.addClass('collapsed');
                    }
                });

				//Delete current or new
				$('#surgeeo_pages').delegate('.surgeeo_page .deleter a', 'click', function(e) {
                    e.preventDefault();

                    var conf = confirm("Are you sure you want to delete this page data?");
                    if(conf === false) {
                        return false;
                    }

                    $this = $(this);

                    if($this.attr('data-id') === '' || $this.attr('data-id') === false) {
                        $this.parents('dl').remove();
                        return false;
                    }

                    $.getJSON($this.attr('href'), {}, function(data) {
                        if(typeof data.status !== 'undefined' && data.status === 'success') {
                            $this.parents('dl').remove();
                        }
                    });
				});
			} else { /** versions since 1.3 **/
				//Create new
				$('#surgeeo_pages .surgeeo_page dt').live('click', function(e) {
					e.preventDefault();

					$this = $(this).parents('dl');

					if($this.data('job') === 'add') {
						_addNew();
						return;
					};
					
					if( $this.hasClass('collapsed') ) {
						$this.removeClass('collapsed');
					} else {
						$this.find('dt span').text($this.find('li.top input').val());
						$this.addClass('collapsed');
					}
				});
				//Delete current or new
				$('#surgeeo_pages .surgeeo_page .deleter a').live('click', function(e) {
					e.preventDefault();

					var conf = confirm("Are you sure you want to delete this page data?");
					if(conf === false) {
						return false;
					}

					$this = $(this);

					if($this.data('id') === '' || $this.data('id') === false) {
						$this.parents('dl').remove();
						return false;
					}

					$.getJSON($this.attr('href'), {}, function(data) {
					    if(typeof data.status !== 'undefined' && data.status === 'success') {
					    	$this.parents('dl').remove();
					    }
					});
				});
			}
		}

		return {
			init:init
		};
	})();
	SurgeEO.init();
});