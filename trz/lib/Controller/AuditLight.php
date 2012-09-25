<?php
namespace trz;

class Controller_AuditLight extends \AbstractController {
    function init(){
        parent::init();
        
        $this->owner->addField("created_dts")->datatype("datetime")->defaultValue(date("Y-m-d H:i:s"))->system(true)->visible(true)->caption("Created");
        $this->owner->addField("modified_dts")->datatype("datetime")->system(true)->visible(true)->caption("Modified");
        $this->owner->addField("deleted_dts")->datatype("datetime")->system(true);
        $this->owner->add("trz/Field_Deleted", "deleted");
        $this->owner->addHook("beforeSave", array($this, "beforeSave"));
        $this->owner->addHook("beforeDelete", array($this, "beforeDelete"));

        $this->owner->addMethod("verify", array($this, "verify"));
    }
    function beforeSave(){
        $this->owner["modified_dts"] = date("Y-m-d H:i:s");
    }
    function beforeDelete($obj, $dsql){
        $this->owner->set(array(
            "deleted" => true,
            "deleted_dts" => date("Y-m-d H:i:s"))
        )->save();
        $dsql->where("1 = 0");
    }
    function verify(){
        $this->owner->set("is_verified", true)->saveAndUnload();
    }
}
