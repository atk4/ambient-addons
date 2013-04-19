4.2 Install notes
================
Installation.

1) Edit your lib/Frontend.php (or alternative API)
        $this->addLocation('/',array(
                    'addons'=>'ambient'
                    )
                )
                ->setParent($this->pathfinder->base_location)
                ->setRelativePath("");

2) Add this into lib/Frontend.php

 $this->add("cms/Controller_Cms");

3) Import doc/*

4) Create page/cms.php

class page_cms extends cms\Page_CmsCore {
}

5) Create page/cmsframe.php

class page_cmsframe extends cms\Page_CmsFrame {
}

6) In admin, add cms toggle swith in menu:

        ->addMenuItem("cms", "Manage CMS")

7) Add page/cmsadmin.php 

class page_cmsadmin extends cms\Page_CmsAdmin {
}

important, in $config["frontend"]["token"] - you have to specify the 'Realm' Frontend is using (check your index.php file)

And that's about it.
