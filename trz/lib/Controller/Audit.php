<?php

namespace trz;
class Controller_Audit extends Controller_AuditLight {
    public $user_class='User';
    public $user_field='user_id';
    function init(){
        parent::init();
        $this->owner->hasOne($this->user_class, $this->user_field)->system(true)->required("Must be selected");
    }
    function beforeSave(){
        if (!$this->owner[$this->user_field]){
            if ($u = $this->api->getUser()){
                if ($u->loaded()){
                    $this->owner[$this->user_field] = $u->id;
                }
            }
        }
        parent::beforeSave();
    }
}

