<?php
namespace cps;

class Model_XML extends \Model {
    public $enclosure = "document";
    public $refs = [];
    public $sub = false; // for sub structures, set to true
    public $source = "XML";
    function init(){
        parent::init();
        $this->setSource($this->add("cps/Controller_Data_" . $this->source), $this->table);
    }
    function hasMany($model, $field){
        $m = $this->add("Model_". $model);
        $this->refs[$model] = [$m, $field, "many"];
        return $m;
    }
    function hasOne($model, $field){
        $m = $this->add("Model_". $model);
        $this->refs[$model] = [$m, $field, "one"];
        return $m;
    }
    function ref($model){
        if (isset($this->refs[$model])){
            list($m,$field,$amount) = $this->refs[$model];
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
