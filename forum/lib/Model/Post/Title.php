<?php
namespace forum;
class Model_Post_Title extends Model_Post {
    public $table_alias = "ft";
    function init(){
        parent::init();
        $this->addCondition("forum_post_id", 0);
        $this->addExpression("last_dts", "coalesce((select updated_dts from forum_post where forum_thread_id = ft.forum_thread_id and forum_post_id = ft.id order by updated_dts desc limit 1), '-')");
        $this->addExpression("last_user", "coalesce((select (select name from user where id = forum_post.user_id) as user from forum_post where forum_thread_id = ft.forum_thread_id and forum_post_id = ft.id order by updated_dts desc limit 1), '-')");
        $this->addExpression("total_posts", "coalesce((select count(*) from forum_post where forum_thread_id = ft.forum_thread_id and forum_post_id = ft.id), '-')");
        $this->getField("name")->mandatory("Must be filled");
        $this->getField("comment")->mandatory("Must be filled");
        $this->setOrder(null, "updated_dts", true);
    }
}
