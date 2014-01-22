<?php
namespace cps;
class Controller_CPS_Profile extends \AbstractController {
    private $cps;
    public $call_tree;
    function init(){
        parent::init();
        $this->cps = $this->add("cps/Controller_CPS", array("debug" => $this->debug));
        $this->call_tree = [];
    }
    function __call($method_name, $args) {
        $t = microtime(true);
        $this->cps->verbose = null;
        $ret =call_user_func_array(array($this->cps, $method_name), $args);
        $t1 = microtime(true);
        $this->call_tree[] = [$method_name, $t1-$t, $this->cps->verbose];
        if ($ret instanceof Controller_CPS){
            return $this;
        }
        return $ret;
    }
    function getTree(){
        return $this->call_tree;
    }
}
