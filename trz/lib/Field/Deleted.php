<?php
/* had to add own, as default was faulty */
namespace trz;
class Field_Deleted extends \Field {
    function init(){
        parent::init();
        $this->defaultValue(false);
        $this->type('boolean')->enum(array("Y", "N"));
        $this->owner->addCondition($this->short_name,false);
    }
}
