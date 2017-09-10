<?php
/*
 * @version     2.7
 * @package     J.B.Weather Widget
 * @copyright   Copyright (C) 2016. All rights reserved.
 * @author      Stachethemes
*/

class JBWeather {
    protected $params;

    function __construct() {
        defined("DS") ? null : define("DS", DIRECTORY_SEPARATOR);
    }
    
    function display() {
                
        require (dirname(__FILE__) . DS . "view" . DS . "tmpl.php");
        
        /*
         * Determine user location
         */
        if ($this->params['autodetect'] == 2):
            $this->params['location'] = $this->getUserIP();
        endif;
        
        $this->_initScript();
    }
    
    function setParams($params) {
        $this->params = $params;
        $this->params["unique"] = $this->unique();
    }
    
    protected function _initScript() {
        
        foreach ($this->params as $opt => $value):
            $params[] = $opt . ':"' . $value . '"';
        endforeach;
        $params = implode(',', $params);
        
        echo "
            <script type='text/javascript'>
                (function($){
                    $(document).ready(function(){
                        new JBWeather('{$this->params["unique"]}').init({{$params}});;
                    });
                })(jQuery);
            </script>
        ";
    }
    
    protected function getUserIP() {
       return $_SERVER['REMOTE_ADDR'];
    }
    
    
    public function unique() {
        return md5(microtime());
    }
}

?>