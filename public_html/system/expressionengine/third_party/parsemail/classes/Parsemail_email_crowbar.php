<?php

// Hack to get access to protected member vars and methods.

class Parsemail_email_crowbar extends EE_Email
{
    private $obj;
    
    public function __construct($obj) 
    {
        $this->obj = $obj;
    }
    
    public function get($var) 
    {
        return $this->obj->$var;
    }

    public function set($var, $value)
    {
        $this->obj->$var = $value;
    }

    public function call($method, $params = array())
    {
        return call_user_func_array(array($this->obj, $method), $params);
    }
}
