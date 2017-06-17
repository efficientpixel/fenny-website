<?php
  
  /**
  * Copyright (c) 2010-2011 Massimiliano Lombardi
  * Plugin licence: http://creativecommons.org/licenses/by-nd/3.0/
  * 
  * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  * THE SOFTWARE. 
  *
  * @note    Config file for EEI_Tcpdf plugin
  * @package EEI_Tcpdf
  */
  
  // where TCPDF is located, w/o trailing slashes (write your specific path)
  $config['eei_tcpdf_lib_path'] = str_replace(
    'expressionengine'._.'third_party'._.'eei_tcpdf'._.'config',
    'application'._.'tcpdf',
    dirname(__FILE__)
  );
  // basedir of your PDF templates (write your specific path)
  $config['eei_tcpdf_template_basedir'] = str_replace(
    'html'._.'system'._.'expressionengine'._.'third_party'._.'eei_tcpdf'._.'config',
    'project',
    dirname(__FILE__)
  );
  // specify to use or not the TCPDF::Image workaround when safe_mode/open_basedir is enabled
  $config['eei_tcpdf_safemode_workaround'] = TRUE; // set FALSE to skip the workaround
  
  /* End of file config.php */
  /* Location: ./system/expressionengine/third_party/eei_tcpdf/config/eei_tcpdf.php */