<?php
namespace cps;

class Model_XML extends \Model {
    public $enclosure = "document";
    public $refs = array();
    public $sub = false; // for sub structures, set to true
    public $source = "XML";
    public $debug = false;
    public $profile = false;
    function init(){
        parent::init();
        $this->setSource($this->add("cps/Controller_Data_" . $this->source, array("debug" => $this->debug, "profile" => $this->profile)), $this->table);
    }
    function hasMany($model, $field){
        $this->refs[$model] = array($model, $field, "many");
        return $this;
    }
    function hasOne($model, $field){
        $this->refs[$model] = array($model, $field, "one");
        return $this;
    }
    function ref($model){
        if (isset($this->refs[$model])){
            list($model,$field,$amount) = $this->refs[$model];
            $m = $this->add("Model_". $model);
            $i = $this->controller->getNode($this, $field);
            $m->controller->_set($m, "root_iterator", $i);
            $m->controller->_set($m, "parent", $this);
            return $m;
        }
        return false;
    }
    function refParent(){
        return $this->controller->_get($this, "parent");
    }
    function getByXpath($xpath, $reversal){
        return $this->controller->getByXpath($this, $xpath, $reversal);
    }
    function escapeXPath($input){
        return preg_replace("/'/", "\\\'/", $input);
    }
}
