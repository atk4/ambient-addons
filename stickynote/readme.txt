StickyNotes
-----------

Use this to annotate your project while you develop it.
Stickynotes can be global or per url (default). Global stickies will be visible
on all pages that have visible StickyNotes

Installation and usage is super easy:

1) clone ambient-addons
git clone git@github.com:atk4/ambient-addons.git ambient-addons

2) inlcude ambient-addons inside your Frontend
        $this->api->addLocation('.',array(
                    'addons'=> array(
                        'ambient-addons'
                    ),
            ))
            ->setRelativePath(".")
            ->setParent($this->api->pathfinder->base_location);

3) import sql scripts for this addon

4) finally, add
    $this->add("stickynote/StickyNote");
inside the pages, that needs notes OR add this into your Frontend to have notes available system-wide.

Enjoy!
info@ambienttech.lv

