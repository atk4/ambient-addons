<?php
namespace cps;

class Controller_CPS extends \AbstractController {
    public $limit = 50;
    public $offset = null;
    public $debug = false;
    public $verbose = "";
    function connect($storage){
        $o = $this->api->getConfig('cps/source/'.$storage);
        $storage = isset($o["storage"])?$o["storage"]:$storage;
        $this->storage = $storage;
        $this->connection = new \CPS_Connection($o["url"], $storage, $o["user"], $o["password"], isset($o["root"])?$o["root"]:"document", isset($o["idpath"])?$o["idpath"]:"//document/id");
        $this->simple = new \CPS_Simple($this->connection);
        $this->debug($this->debug);
        return $this;
    }
    function debug($debug){
        $this->connection
            ->setDebug($debug);
        return $this;
    }
    function insert($model, &$data){
        /* insert new record and store back to CPS
         *
         * note, if new SUB is added, ask ID to model. otherwise, if it's not 
         * sub, then we can ask CPS to auto increment
         *
         * if we have no ID, pass nothing to CPS then autoincrement will be 
         * triggered */
        /* and store iterator */
        if ($model->sub){
            $iterator = null;
            if ($xml = $model->_get("xml")){
                /* one to many */
                $iterator = $xml->addChild($model->enclosure);
                if (!isset($data[$model->id_field])){
                    $max = 0;
                    foreach ($xml->{$model->enclosure} as $e){
                        $max = max($max, (int)(string)$e->{$model->id_field});
                    }
                    $data[$model->id_field] = $new_id = $max+1;
                    $model->{$model->id_field} = $new_id;
                }
            } else {
                $iterator = $model->_get("iterator");
            }
            if ($iterator === null){
                throw $this->exception("Cannot insert new record - iterator not available");
            }
            foreach ($data as $k => $v){
                if ($model->elements[$k]->setterGetter("xpath")){
                    throw $this->exception("Inserting sub-model field by xpath not supported yet");
                }
                $iterator->{$k} = $v;
            }
            $model->_get("parent")->save();
        } else {
            /* store to cluster point */
            $iterator = new \StdClass();
            foreach ($data as $k => $v){
                if ($xpath=$model->elements[$k]->setterGetter("xpath")){
                    $xpath = explode("/", $xpath);
                    $i2 = $iterator;
                    while ($r = array_shift($xpath)){
                        if (count($xpath)){
                            if (!isset($i2->{$r})){
                                $i2->{$r} = new \StdClass();
                            }
                            $i2 = $i2->{$r};
                        } else {
                            $i2->{$r} = $v;
                        }
                    }
                } else{
                    $iterator->{$k} = $v;
                }
            }
            $id_field = $model->id_field;
            $id = null; // leave for auto increment
            if (isset($iterator->{$id_field})){
                $id = $iterator->{$id_field};
            }
            $this->simple->insertSingle($id, $iterator);
            if (!$id){
                $ids=$this->simple->response->getModifiedIds();
                $model->id = (string)$ids[0];
            } else {
                $model->id = $id;
            }
        }
        return $iterator;
    }
    function update($model, $data){
        /* partial update, store back to CPS */
        /* set data into iterator */
        $iterator = $model->_get("iterator");
        if (!$model->loaded()){
            throw $this->exception("Please, load model prior to updating it");
        }
        if (!$iterator){
            throw $this->exception("Iterator not set for model - how to update? Is it loaded?");
        }
        foreach ($data as $k => $v){
            if ($xpath=$model->elements[$k]->setterGetter("xpath")){
                $a = $iterator->xpath($xpath);
                if ($a){
                    $i = array_shift($a);
                    $i[0] = $v;
                } else {
                    /* create xpath */
                    $xpath = explode("/", $xpath);
                    $i2 = $iterator;
                    while ($r = array_shift($xpath)){
                        if (count($xpath)){
                            if (!isset($i2->{$r})){
                                $i2->addChild($r);
                            }
                            $i2 = $i2->{$r};
                        } else {
                            $i2->{$r}[0] = $v;
                        }
                    }
                }
            } else {
                $iterator->{$k} = $v;
            }
        }
        /* remove snippets from iterator */
        $remove = [];
        foreach ($model->elements as $k => $v){
            if ($v instanceof \Field){
                if ($r=$v->setterGetter("retrieve")){
                    if ($r != "yes"){
                        throw $this->exception("Cannot update model, as it contains non-fully loaded fields");
                    }
                }
            }
        }
        if ($model->sub){
            $model->_get("parent")->save();
        } else {
            /* store to cluster point */
            /*$a=$this->xml2array($iterator);
            echo "<pre>";
            print_r($a);
            exit;*/
            //$this->simple->partialReplaceSingle($model[$model->id_field], $a);
            $this->simple->updateSingle($model[$model->id_field], $iterator);
        }
    }
    function xml2array($xml){
        $a = json_encode($xml);
        $a = json_decode($a,true);
        return $a;
    }
    function rewind($model){
        /* not loaded, load */
        if (isset($model->sub) && $model->sub){
            if (($i=$model->_get("iterator")) instanceof \SimpleXMLIterator){
                return [$i, $i];
            } else if ($xml = $model->_get("xml")){
                $p = $xml->{$model->enclosure};
                /*
                 * Process the p to:
                 * 1) new object, sorted according to sort rules,
                 * 2) leave only results that match
                 */
                $p->rewind();
                $processed_xml = $this->processXML($p, $model);
                list($limit,$offset) = $model->_get("limit");
                $this->counter = 0;
                if ($offset > count($processed_xml)){
                    return false;
                }
                if ($offset > 0){
                    while ($this->counter < $offset){
                        $this->counter++;
                        next($processed_xml);
                    }
                }
                $model->_set("count", count($processed_xml)); // should count properly I suppose
                $current = current($processed_xml);
                return [$processed_xml, $current["_iterator"]];
            }
            /* parent should be loaded */
            throw $this->exception("Trying to iterate submodel, when parent is not loaded!");
        } else {
            $xml = $model->_get("xml");
            /* not sub, top. search */
            $list = [];
            /* add all relevant fields */
            foreach ($model->getActualFields() as $field){
                if ($retrieve = $model->elements[$field]->setterGetter("retrieve")){
                    $type = $retrieve;
                } else {
                    $type = "yes";
                }
                if ($xpath = $model->elements[$field]->setterGetter("xpath")){
                    $field = $xpath;
                }
                $list[$field] = $type;
            }
            $query = $this->buildQuery($model);
            $order = $this->buildOrder($model);
            list ($limit, $offset) = $model->_get("limit");
            $limit = ($limit!==null)?$limit:10;
            $offset = $offset?:0;
            ob_start();
            $xml = $this->buildXML($d=$this->simple->search($query, $offset, $limit, $list, $order, "DOC_TYPE_XMLIterator"));
            $this->verbose = ob_get_contents();
            ob_end_clean();
            $model->_set("xml", $xml);
            $model->_set("count", $this->simple->response->getParam("hits"));
            $xml->rewind();
            return [$xml, $xml->current()];
        }
    }
    function processXML($xml, $model){
        $a = [];
        $conditions = $model->_get("conditions");
        while (true){
            $c = $xml->current();
            if (!$c){
                break;
            }
            if ($this->match($c, $conditions)){
                $row = $this->xml2array($c);
                $row2 = [];
                foreach ($row as $k => $v){
                    if (is_array($v)){
                        /* this should not be so */
                        $row2[$k] = null;
                    } else {
                        $row2[$k] = $v;
                    }
                }
                $row2["_iterator"] = $c;
                $a[] = $row2;
            }
            $xml->next();
        }
        if ($o = $model->_get("order")){
            foreach ($o as $k => $v){
                $e = $model->hasField($v[0]);
                if ($v[0] && !$e){
                    throw $this->exception("Sort on non-existing field: ". $v[0]);
                }
                if ($e && $xpath = $e->setterGetter("xpath")){
                    throw $this->exception("X path in supported yet");
                } else {
                    $field = $v[0];
                }
                $descending = ($v[1] == "descending")?-1:1;
                if ($v[2] == "numeric"){
                    uasort($a, function($i,$j) use ($field, $descending){
                        if ($i[$field] == $j[$field]){
                            return 0;
                        }
                        return $descending * (((float)$i[$field] < (float)$j[$field]) ? -1 : 1);
                    });
                } else if ($v[2] == "string"){
                    uasort($a, function($i,$j) use ($field, $descending){
                        if ($i[$field] == $j[$field]){
                            return 0;
                        }
                        return $descending * (((string)$i[$field] < (string)$j[$field]) ? -1 : 1);
                    });
                } else if ($v[2] == "date"){
                    usort($a, function($i,$j) use ($field,$descending){
                        $i1 = strtotime($i[$field]);
                        $i2 = strtotime($j[$field]);
                        if ($i1 === $i2){
                            return 0;
                        }
                        return $descending * (($i1 < $i2) ? -1 : 1);
                    });
                } else {
                    throw $this->exception("No idea how to sort this way")->addMoreInfo("sort direction", $v[2]);
                }
            }
        }
        return $a;
    }

    function find($iterator, $conditions){
        $iterator->rewind();
        foreach ($iterator as $elem){
            if ($this->match($elem, $conditions)){
                return $elem;
            }
        }
    }
    function match($xml, $cond){
        if (!empty($cond)){
            foreach ($cond as $k => $vv){
                foreach ($vv as $v){
                    $child = (string)$xml->$k;
                    if ((string)$v != $child){
                        return false;
                    }
                }
            }
        }
        return true;
    }
    function buildXML($array){
        $xml = simplexml_load_string('<?'.'xml version="1.0" encoding="UTF-8"'.'?><documents></documents>', 'SimpleXMLIterator');
        foreach ($array as $child){
            $d = $xml->addChild("document");
            $this->appendXML($d, $child);
        }
        return $xml;
    }
    function appendXML($ptr, $what){
        foreach ($what as $k => $v){
            if ($v->children()){
                $p = $ptr->addChild($k);
                $this->appendXML($p, $v);
            } else {
                $ptr->addChild($k, strtr((string)$v, ["&"=>"&amp;"]));
            }
        }
    }
    function buildOrder($model){
        if ($o = $model->_get("order")){
            $os = [];
            foreach ($o as $k => $v){
                $e = $model->hasField($v[0]);
                if ($v[0] && !$e){
                    throw $this->exception("Sort on non-existing field: ". $v[0]);
                }
                if ($e && $xpath = $e->setterGetter("xpath")){
                    $field = $xpath;
                } else {
                    $field = $v[0];
                }
                if ($v[2] == "numeric"){
                    $os[] = CPS_NumericOrdering($field, $v[1]); 
                } else if ($v[2] == "date"){
                    $os[] = CPS_DateOrdering($field, $v[1]); 
                } else if ($v[2] == "string"){
                    $os[] = CPS_StringOrdering($field, $v[3],$v[1]); 
                } else if ($v[2] == "relevance"){
                    $os[] = CPS_RelevanceOrdering();
                } else {
                    throw $this->exception("No idea how to sort this way")->addMoreInfo("sort_field", $v[2]);
                }
            }
            return $os;
        }
        return null;
    }
    function buildQuery($model){
        $query = "";
        if ($c=$model->_get("conditions")){
            foreach ($c as $k => $vv){
                if ($xpath = $model->elements[$k]->setterGetter("xpath")){
                    $k = $xpath;
                }
                foreach ($vv as $v){
                    if (is_array($v)){
                        if ($v[1] == "like"){
                            $r[] = CPS_Term("*".$v[0]."*", $k);
                        } else if ($v[1] == "!="){
                            $r[] = CPS_Term("~".$v[0], $k);
                        } else if ($v[1] == ">="){
                            $r[] = CPS_Term(">= ".$v[0], $k);
                        } else if ($v[1] == "<="){
                            $r[] = CPS_Term("<= ".$v[0], $k);
                        } else {
                            throw $this->Exception("Sorry, this comparison " . $v[1] . " is not yet supported");
                            $r[] = CPS_Term($v[0], $k);
                        }
                    } else {
                        $r[] = CPS_Term($v, $k);
                    }
                }
            }
            $query = implode(" ", $r);
            /* apply */
        }
        if ($q=$model->_get("query")){
            $query .= $query?" ":"";
            $query .= $q;
        }
        if (!$query){
            $query = "*";
        }
        return $query;
    }
    function next($model, &$ptr){
        if (isset($model->sub) && $model->sub){
            /* here we do soft params */
            /* technically this is not correct. cps should not handle this.
             * sub-xml models should be handled by xml processor */
            list($limit,$offset) = $model->_get("limit");
            $conditions = $model->_get("conditions");
            $this->counter++;
            if ($limit && ($this->counter > $limit+$offset-1)){
                return null;
            }
            while (true){
                $c = next($ptr);
                if (!$c){
                    return null;
                }
                return (object)$c;
            }
        } else {
            /* here we already have pre-filtered results */
            $ptr->next();
            if ($ptr->valid()){
                return $ptr->current();
            } else {
                return null;
            }
        }
    }
    function count($model){
        $l = $model->_get("limit");
        $model->_set("limit", [0,0]);
        $this->rewind($model);
        $model->_set("limit",$l);
        $c = $model->_get("count");
        return $c;
    }
    function delete($model, $id=null){
        if ($model->sub){
            if ($xml=$model->_get("xml")){
                if (!$id){
                    $i = $model->_get("iterator");
                    if (!$i){
                        throw $this->exception("Either load model before deleteing, or specify id");
                    } else {
                        $id = $i->{$model->id_field};
                    }
                }
                $r=$this->rewind($model);
                if ($r){
                    list($ptr, $current) = $r;
                    $counter = 0;
                    foreach ($ptr as $k => $c){
                        if ($c[$model->id_field] == $id){
                            $parent = $c["_iterator"]->xpath("parent::*");
                            unset($parent[0]->{$model->enclosure}[$counter]);
                            $model->_get("parent")->save();
                            return;
                        }
                        $counter++;
                    }
                }
            }
        } else {
            if ($model->loaded()){
                $this->simple->delete($model[$model->id_field]);
            } else if ($id){
                $this->simple->delete($id);
            } else {
                throw $this->exception("Load before delete");
            }
        }
    }
    function deleteAll($model){
        if ($model->sub){
            if ($xml=$model->_get("xml")){
                if (!$id){
                    $i = $model->_get("iterator");
                    if (!$i){
                        throw $this->exception("Either load model before deleteing, or specify id");
                    } else {
                        $id = $i->{$model->id_field};
                    }
                }
                $r=$this->rewind($model);
                if ($r){
                    list($ptr, $current) = $r;
                    $counter = 0;
                    foreach ($ptr as $k => $c){
                        unset($xml->{$k}[$counter]);
                    }
                    $model->_get("parent")->save();
                }
            }
        } else {
            $this->simple->searchDelete($this->buildQuery($model));
        }
    }

    function getRootIterator(){
        return $this->xml;
    }
    function getNode($model, $field){
        $iterator = $model->_get("iterator");
        if (isset($iterator->{$field})){
            return $iterator->{$field};
        } else {
            return $iterator->addChild($field);
        }
    }
    function getFacets($model, $path){
        if (isset($model->sub) && $model->sub){
            throw $this->exception("Sorry this is available only at top level");
        }
        $query = $this->buildQuery($model);
        $s = new \CPS_SearchRequest($query, 0, 0);
        $s->setFacet($path);
        $r = $this->connection->sendRequest($s);
        $facets = $r->getFacets();
        if (isset($facets[$path])){
            return $facets[$path];
        }
        return null;
    }
    function getAggregate($model, $aggregate, $extra_conditions=null){
        if (isset($model->sub) && $model->sub){
            throw $this->exception("Sorry this is available only at top level");
        }
        $b = [];
        $ref = [];
        foreach ($aggregate as $k=>$v){
            $b[] = $r = CPS_term($v);
            $ref[$r] = $k;
        }
        $query = $this->buildQuery($model);
        if ($extra_conditions){
            $c = [];
            foreach ($extra_conditions as $k=>$e){
                $c[] = CPS_Term($e,$k);
            }
            if ($query != "*"){
                $query .= " " . implode(" ", $c);
            } else {
                $query = implode(" ", $c);
            }
        }
        $s = new \CPS_SearchRequest($query, 0, 0);
        $s->setAggregate($b);
        $r = $this->connection->sendRequest($s);
        $ret = [];
        if ($a=$r->getAggregate()){
            foreach ($a as $k => $v){
                if (!isset($ref[$ref[$k]])){
                    $ret[$ref[$k]] = [];
                }
                foreach ($v as $vv){
                    $ret[$ref[$k]][] = $vv;
                }
            }
        }
        return $ret;
    }
}
