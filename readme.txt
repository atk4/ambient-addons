Installation.

1) Edit your lib/Frontend.php (or alternative API)

add locations:
        $this->addLocation('cms',array(
                'php'=>array(
                    'lib',
                ),
                'js'=> array(
                    'templates/js'
                ),
                'template'=> array(
                    'templates/' . $this->skin
                ),
                'css'=> array(
                    'templates/' . $this->skin . '/css'
                )
            ))
            ->setParent($this->pathfinder->base_location);

2) Add this into lib/Frontend.php

 $this->add("Controller_Cms");

3) Import doc/*

4) Create page/cms.php

class page_cms extends Page_CmsCore {
}

5) Create page/cmsframe.php

class page_cms extends Page_CmsCore {
}

6) In admin, add cms toggle swith in menu:

           ->addMenuItem('CMS settings', 'cms')
           ->addMenuItem('Go CMS', 'cmsmode')

7) Add page/cmsmode.php 

class page_cmsmode extends Page_CmsMode {
}

important, in $config["frontend"]["token"] - you have to specify the token that the Frontend is using for session separation

8) Add page/cms.php

class page_cms extends Page_CmsAdmin {}


And that's about it.
