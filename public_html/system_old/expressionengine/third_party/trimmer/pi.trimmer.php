<?php if ( ! defined('EXT')) { exit('Invalid file request'); }
   /*
   ========================================================
   Plugin Trimmer
   --------------------------------------------------------
   Copyright: Oliver Heine
   License:		Freeware
   http://utilitees.de/ee.php/trimmer/
   --------------------------------------------------------
 	 This addon may be used free of charge. Should you
 	 employ it in a commercial project of a customer or your
 	 own I'd appreciate a small donation.
   ========================================================
   File: pi.trimmer.php
   --------------------------------------------------------
   Purpose: Removes characters from the beginning and / or 
   					end of a given text.
	========================================================
 	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF
 	ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT 
 	LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 	FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO 
 	EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 	FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN
 	AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE 
 	OR OTHER DEALINGS IN THE SOFTWARE.
	========================================================
   
   */
  
  $plugin_info = array('pi_name' => 'Trimmer', 
  										 'pi_version' => '2.0', 
  										 'pi_author' => 'Oliver Heine', 
  										 'pi_author_url' => 'http://utilitees.de/ee.php/trimmer/', 
  										 'pi_description' => 'Removes characters from the beginning and / or the end of a given text.', 
  										 'pi_usage' => trimmer::usage());
  
  class Trimmer
  {
      var $return_data;
      
      function Trimmer()
      {
          $this->EE =& get_instance();
          
          $text = trim($this->EE->TMPL->tagdata);
          
          $left = $this->EE->TMPL->fetch_param('left') + 0;
          $right = $this->EE->TMPL->fetch_param('right') + 0;
          
          $stop_before = ($this->EE->TMPL->fetch_param('stop_before'));
          $start_after = ($this->EE->TMPL->fetch_param('start_after'));
         
          if ( isset($start_after) )
          {
          	if (strpos($text,$start_after) !== FALSE)
          	{
          		$text = substr($text, strpos($text,$start_after)+strlen($start_after) );
          	}
          }

          if ( isset($stop_before) )
          {
          	if (strpos($text,$stop_before) !== FALSE)
          	{
          		$text = substr($text, 0, strpos($text,$stop_before) );
          	}
          }

          if ($left > 0) {                                                     
            $text = substr($text, $left);
          }

          if ($right > 0) {                                                     
            $text = substr($text, 0, strlen($text)-$right);
          }
                    
          $this->return_data = $text;
      }
      
      // ----------------------------------------
      //  Plugin Usage
      // ----------------------------------------
      // This function describes how the plugin is used.
      //  Make sure and use output buffering
      function usage()
      {
          ob_start();
?>

Removes characters from the beginning and / or end of a given text and returns 
the trimmed string.
It can either remove a fixed number of characters or cut the text at positions 
defined by a given string.



Tag:
----
{exp:trimmer}{/exp:trimmer}


Parameters:
-----------
start_after="foo"
Removes anything before the first occurence of "foo", including "foo".

stop_before="bar"
Removes anything after the first occurence of "bar", including "bar".

left="X"
cuts X characters from the beginning of a text 

right="X"
cuts X characters from the end of a text

Example:
----------------
{exp:trimmer left="3" right="4"}
<p>Some text to trim.</p>
{/exp:trimmer}

returns:

Some text to trim.

Example 2:
----------------
{exp:trimmer start_after="learn" stop_before="c"}
Next week, we'll learn how to defend ourselves against someone carrying grapes.
{/exp:trimmer}

returns:

 how to defend ourselves against someone 

Note:
-----
You can combine any parameters as long as you keep in mind the order in which 
they are executed.
1. start_after
2. stop_before
3. left
4. right

<?php
          $buffer = ob_get_contents();
          ob_end_clean();
          return $buffer;
      }
      /* END */
      
  }
  // END CLASS
/* End of file pi.trimmer.php */
/* Location: ./system/expressionengine/third_party/pi.trimmer.php */