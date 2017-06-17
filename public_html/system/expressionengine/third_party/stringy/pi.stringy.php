<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Stringy Class
 *
 * @package     ExpressionEngine
 * @category    Plugin
 * @author      Glenn Jacibs
 * @copyright   Copyright (c) 2012, Glenn Jacobs
 * @link        http://www.glennjacobs.co.uk/
 */

$plugin_info = array(
  'pi_name'         => 'Stringy',
  'pi_version'      => '1.1',
  'pi_author'       => 'Glenn Jacobs',
  'pi_author_url'   => 'http://www.glennjacobs.co.uk/',
  'pi_description'  => 'A helpful collection of string manipulation functions',
  'pi_usage'        => Stringy::usage()
);

class Stringy {


    public function __construct()
    {
        $this->EE =& get_instance();
    }
    
    
    public function nl2br()
    {
        return nl2br($this->EE->TMPL->tagdata);
    }

    public function lowercase()
    {
        return strtolower($this->EE->TMPL->tagdata);
    }
    
    public function uppercase()
    {
        return strtoupper($this->EE->TMPL->tagdata);
    }
    
    public function upperfirst()
    {
        return ucwords(strtolower($this->EE->TMPL->tagdata));
    }
	
    public function removeemptyline()
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $this->EE->TMPL->tagdata);
    }
    
    public function slug()
    {
        $separator = $this->EE->TMPL->fetch_param('separator') ? $this->EE->TMPL->fetch_param('separator') : "-";
        $case = $this->EE->TMPL->fetch_param('separator') ? $this->EE->TMPL->fetch_param('case') : "LOWER";
        
        switch (strtoupper($case)) {
        	case "UPPER":
        		$string = strtoupper($this->EE->TMPL->tagdata);
        		break;
        		
        	case "NOCHANGE":
        		$string = $this->EE->TMPL->tagdata;
        		break;
        		
        	default:
        		$string = strtolower($this->EE->TMPL->tagdata);
        }
        
        
        return preg_replace('/[^A-Za-z0-9-]+/', $separator, $string);
    }
      
    
    // strip tags
    public function striptags() 
    {
        $allowed_tags = $this->EE->TMPL->fetch_param('allowed_tags');
        
        if ($allowed_tags)
        {
            return strip_tags($this->EE->TMPL->tagdata,$allowed_tags);
        }
        else
        {
            return strip_tags($this->EE->TMPL->tagdata);
        }
    }
    
    
    // char limit
    public function limitchars()
    {
        $limit = (int)$this->EE->TMPL->fetch_param('limit');
        
        return substr($this->EE->TMPL->tagdata,0,$limit);
    }
    
    
    // string replace
    public function replace()
    {
        $find = $this->EE->TMPL->fetch_param('find');
        $replace = $this->EE->TMPL->fetch_param('replace');
        
        return str_replace($find,$replace,$this->EE->TMPL->tagdata);
    }
    
    
    // string length
    public function length()
    {       
        return strlen($this->EE->TMPL->tagdata);
    }
    
    
    // word count
    public function wordcount()
    {       
        return str_word_count($this->EE->TMPL->tagdata);
    }
    
    
    // trim, left trim, right trim
    public function trim()
    {       
        $option = $this->EE->TMPL->fetch_param('option');
        
        switch ($option)
        {
            case "left":
                return ltrim($this->EE->TMPL->tagdata);
                break;
                
            case "right":
                return rtrim($this->EE->TMPL->tagdata);
                break;
                
            default:
                return trim($this->EE->TMPL->tagdata);
        }
        
    }
    
    
    // wordwrap
    public function wordwrap()
    {
        $width = $this->EE->TMPL->fetch_param('width') ? $this->EE->TMPL->fetch_param('width') : 75;
        $break = $this->EE->TMPL->fetch_param('break') ? $this->EE->TMPL->fetch_param('break') : "\n";
        $cut = ($this->EE->TMPL->fetch_param('cut') == "true") ? TRUE : FALSE;
        
        echo $cut;
        
        return wordwrap($this->EE->TMPL->tagdata,$width,$break,$cut);
        
    }
    
    
    // shuffle (random)
    public function shuffle()
    {
        return str_shuffle($this->EE->TMPL->tagdata);
    }
        
        
    // html entities
    public function htmlentities()
    {
        return htmlentities($this->EE->TMPL->tagdata);
    }
    
    
    // html entity decode
    public function htmlentitydecode()
    {
        return html_entity_decode($this->EE->TMPL->tagdata);   
    }
    
    
    // strpad
    public function pad()
    {
        $length = $this->EE->TMPL->fetch_param('length') ? (int)$this->EE->TMPL->fetch_param('length') : 75;
        $string = $this->EE->TMPL->fetch_param('string') ? $this->EE->TMPL->fetch_param('string') : " ";

        switch ($this->EE->TMPL->fetch_param('type'))
        {
            case "left":
                $type = STR_PAD_LEFT;
                break;
                
            case "both":
                $type = STR_PAD_BOTH;
                break;
                
            default:
                $type = STR_PAD_RIGHT;
                break;
        }
        
        return str_pad($this->EE->TMPL->tagdata,$length,$string,$type);   
    }
    
    
    // number format
    public function numberformat()
    {
        $decimals = $this->EE->TMPL->fetch_param('decimals') ? (int)$this->EE->TMPL->fetch_param('decimals') : 0;
        $dec_point = $this->EE->TMPL->fetch_param('dec_point') ? $this->EE->TMPL->fetch_param('dec_point') : ".";
        $thousands_sep = $this->EE->TMPL->fetch_param('thousands_sep') ? $this->EE->TMPL->fetch_param('thousands_sep') : ",";
        
        return number_format((float)$this->EE->TMPL->tagdata,$decimals,$dec_point,$thousands_sep);   
    }
    
    
    
    // --------------------------------------------------------------------

    /**
     * Usage
     *
     * This function describes how the plugin is used.
     *
     * @access  public
     * @return  string
     */
    public static function usage()
    {
        ob_start();  ?>

Stringy provides the following useful text manipulations.

Below are example usages...

    {exp:stringy:nl2br}{somevariable}{/exp:stringy:nl2br}

    {exp:stringy:lowercase}GLENN JACOBS{/exp:stringy:lowercase}
    
    {exp:stringy:uppercase}glenn jacobs{/exp:stringy:uppercase}
    
    {exp:stringy:upperfirst}glenn jacobs{/exp:stringy:upperfirst}
    
    {exp:stringy:slug separator="-" case="lower"}My Nice Title{/exp:stringy:slug}
    	- case [=lower, upper, nochange]
        
    {exp:stringy:striptags allowed_tags="<b>"}{/exp:stringy:striptags}
        
    {exp:stringy:limitchars limit="75"}Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer dictum.{/exp:stringy:limitchars}
        
    {exp:stringy:replace find="fun" replace="cool"}ExpressionEngine is so very fun!{/exp:stringy:replace}
        
    {exp:stringy:length}123456789{/exp:stringy:length}
    
    {exp:stringy:wordcount}One Two Three Four Five Six{/exp:stringy:wordcount}
    
    {exp:stringy:trim option="left"} I need trimming! {/exp:stringy:trim}
        - option [left,right,both]
        
    {exp:stringy:wordwrap width="75" break="\n" cut="false"}This is a loooooooooooooooooong word{/exp:stringy:wordwrap}
        
    {exp:stringy:shuffle}abcdefghijkl{/exp:stringy:shuffle}
    
    {exp:stringy:htmlentities}<p>Some nice char's etc.{/exp:stringy:htmlentities}
    
    {exp:stringy:htmlentitydecode}&lt;b&gt;Stuff&lt;/b&gt;{/exp:stringy:htmlentitydecode}
    
    {exp:stringy:pad length="75" string=" " type="right"}Pad me please{/exp:stringy:pad}
        - type [=right, left, both]
        
    {exp:stringy:numberformat decimals="0" dec_point="." thousands_sep=","}1234567890{/exp:stringy:numberformat}
     
        

    <?php
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
    // END
    
}

/* End of file pi.stringy.php */
/* Location: ./system/expressionengine/third_party/stringy/pi.stringy.php */