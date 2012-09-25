<?php
namespace forum;
class Model_Thread extends \Model_Table {
    public $table = "forum_thread";
    function init(){
        parent::init();
        $this->addField("name")->required("Must be filled");
        $this->hasOne("forum/Category", "forum_category_id");
        $this->add("forum/Controller_Owner");
        $this->addExpression("last_dts", "coalesce((select updated_dts from forum_post where forum_thread_id = forum_thread.id order by updated_dts desc limit 1), '-')");
        $this->addExpression("last_user", "coalesce((select (select name from user where id = forum_post.user_id) as user from forum_post where forum_thread_id = forum_thread.id order by updated_dts desc limit 1), '-')");
        $this->addExpression("total_posts", "coalesce((select count(*) from forum_post where forum_thread_id = forum_thread.id), '-')");
    }
}
