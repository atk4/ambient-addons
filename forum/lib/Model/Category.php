<?php
namespace forum;
class Model_Category extends \Model_Table {
    public $table = "forum_category";
    function init(){
        parent::init();
        $this->addField("name")->required("Must be filled");
        $this->hasMany("forum/Thread");
        $this->addExpression("threads", "(select count(*) from forum_thread where forum_category_id = forum_category.id)");
    }
}
