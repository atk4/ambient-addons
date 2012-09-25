<?php

namespace trz;
class Controller_Audit extends Controller_AuditLight {
    function init(){
        parent::init();
        $this->owner->hasOne("User", "user_id")->system(true)->required("Must be selected");
    }
    function beforeSave(){
        if (!$this->owner["user_id"]){
            if ($u = $this->api->getUser()){
                if ($u->loaded()){
                    $this->owner["user_id"] = $u["id"];
                }
            }
        }
        parent::beforeSave();
    }
}

