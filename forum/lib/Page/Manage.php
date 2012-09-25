<?php

namespace forum;
class Page_Manage extends \Page {
    function initMainPage(){
        $t = $this->add("Tabs");
        $t->addTabURL("./categories", "Categories");
        $t->addTabURL("./threads", "Threads");
        $t->addTabURL("./posts", "Posts");
    }
    function page_categories(){
        $this->add("CRUD")->setModel("forum/Category");
    }
    function page_threads(){
        $this->add("CRUD")->setModel("forum/Thread");
    }
    function page_posts(){
        $this->add("CRUD")->setModel("forum/Post");
    }
}
