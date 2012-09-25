<?php
namespace trz;
class Model_Transaction_My extends Model_Transaction {
    function init(){
        parent::init();
        if ($u = $this->api->getUser()){
            if ($u->loaded()){
                $this->setMasterField("user_id", $u["id"]);
            }
        }
    }
}
