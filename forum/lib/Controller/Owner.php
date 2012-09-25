<?php
namespace forum;
class Controller_Owner extends \AbstractController {
    function init(){
        parent::init();
        $this->owner->addField("created_dts")->datatype("string")->defaultValue(date("Y-m-d H:i:s"));
        $this->owner->addField("updated_dts")->datatype("string")->defaultValue(date("Y-m-d H:i:s"));
        $this->owner->hasOne("User"); //if not set, then anonymous entries are supported
    }
}
