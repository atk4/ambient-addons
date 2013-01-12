---------------------------------------------------------------------
Spread Editor, by Ambient Technologies, 2013 (c)
---------------------------------------------------------------------

Displays Model records in a table, allowing multiple editing of those.
Fields are rendered as in any other form, MVC_Form controller is used
to properly add form fields. All validators would work as expected,
except, that due to form_empty layout, errors would not appear on the
grid. Maybe there will be update for that in future.

Usage is super simple:

1) Add ambient-addons to your inlude path.
2) In your view/page add following code:

    $v=$this->add("spreadedit/View_Edit");
    $v->setModel("Foo");
    $v->add("Paginator", null, "paginator")
        ->ipp(10)
        ->setSource($v->getModel());

Mind that internal limit for Spread Editor is 10 rows, but you can
increse that in following way:

    $v=$this->add("spreadedit/View_Edit", array("limit" => 20));

However, be careful, as depending on the field count in the model,
generation of a huge form takes some time and memory.


---------------------------------------------------------------------
support&info: info@ambienttech.lv
visit us at http://www.ambienttech.lv
---------------------------------------------------------------------
