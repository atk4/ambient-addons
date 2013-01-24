M2M is addon for managing hasMany records.
---------------------------------------------------------------
Requirements:
1) Atk4 master branch
2) autocomplete add-on (git@github.com:atk4/autocomplete.git)
-- suggested to use exp branch

---------------------------------------------------------------
Consider following layout

1. ModelA
-hasMany("AB")

2. ModelB 

3. ModelAB
-hasOne("A")
-hasOne("B")


M2m would let you edit ModelAB records in a nice way.

On top of that, there are following customization options:

1. toggleAutoComplete() -- enables/disables autocomplete lookup for records in ModelB
2. toggleAdd() -- enables/disables adding new records into ModelB
3. setCategory($field) -- adds filter form, which assumes that
ModelB has hasOne(ModelB_Category, $field)
- in Filter you would see ModelB_Category records, where as main add/remove
  form would shown only ModelB records matching to the chosen category
4. setOption($k, $v) - various options, such as:
4.1 limit -- items per page in edit view
4.2 columns -- columns of checkboxes in edit view

Base case example usage:

$l = $this->add("m2m/View_List");
$l->setModels($a, $b, "B");

note:
$a must be model and loaded
$b can be either name of model or model object
"AB" is the name of the model that ModelA has as hasMany in it. 

Demo: http://new.ambienttech.lv/m2m
and lib/Page/Example.php
