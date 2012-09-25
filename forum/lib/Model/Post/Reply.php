<?php
namespace forum;
class Model_Post_Reply extends Model_Post {
    function init(){
        parent::init();
        $this->addCondition("forum_post_id", "!=", 0);
    }
}
