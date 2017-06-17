<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Password_IBField extends IBFieldtype {

	public $input_type;

	public function display_field($data = '')
	{
		if(empty($data))
		{
			$data = $this->default;
		}

		return '<input type="password" name="'.$this->name.'" value="'.$this->form_prep($data).'" id="'.$this->id.'" />';
	}
}