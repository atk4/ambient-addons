<?php
namespace cps;
class Model_CPS extends Model_XML {
    public $source = "CPS";
    function ref($model){
        if (isset($this->refs[$model])){
            list($m,$field,$amount) = $this->refs[$model];
            if ($amount == "many"){
                $i = $this->controller->getNode($this, $field);
                $m->_set("xml", $this->_get("iterator"));
                $m->_set("field", $field);
            } else {
                $i = $this->controller->getNode($this, $field);
                $m->_set("iterator", $i);
                $m->tryLoadAny();
            }
            $m->_set("parent", $this);
            return $m;
        }
        return false;
    }
    function setOrder($field, $direction, $type = "numeric", $aux=null){
        $order = $this->_get("order")?:[];
        $order[] = [$field, $direction, $type, $aux];
        $this->_set("order", $order);
        return $this;
    }
}
