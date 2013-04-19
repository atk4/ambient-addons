<?php
namespace trz;
class Model_Transaction_My extends Model_Transaction {
    function init(){
        parent::init();

        $u = $this->api->methodExists('getUser') ? $this->api->getUser() :
            ($this->api->auth ? $this->api->auth->model : null );

        if ($u && $u->loaded()){
            $this->addCondition("user_id", $u["id"]);
        }
    }
}
