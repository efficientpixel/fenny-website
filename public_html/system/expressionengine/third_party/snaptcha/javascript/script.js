$(document).ready(function()
{
	$('select[name=security_level]').change(function() {
		$('.member_registration_html textarea').hide();
		$('.member_registration_html textarea:nth-of-type(' + $(this).val() + ')').show();
	});

	$('select[name=member_registration_validation], select[name=logging]').change(function() {
		if ($(this).val() == 1) {
			$(this).next().show();
		}
		else {				
			$(this).next().hide();
		}
	});
	
	$('select[name=security_level]').change();
	$('select[name=member_registration_validation]').change();
	$('select[name=logging]').change();
});