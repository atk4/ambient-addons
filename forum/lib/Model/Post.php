<?php
namespace forum;
class Model_Post extends \Model_Table {
    public $table = "forum_post";
    function init(){
        parent::init();
        $this->addField("name"); //title
        $this->addField("comment")->datatype("text");
        $this->hasOne("forum/Thread", "forum_thread_id");
        $this->hasOne("forum/Post", "forum_post_id");
        $this->add("forum/Controller_Owner");
        $this->posts = $this->hasMany("forum/Post", "forum_post_id", "id");
    }
}
