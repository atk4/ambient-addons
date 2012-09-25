<?php
/*
 * Transactions are used in financial applications and represent flow of 
 * resources from one user to another. This Addon assumes, that you have 
 * eco-system, that has Model_User, and that your api has autorization added to 
 * determine transactions for the user.
 *
 * This addon will also provide with interface for user to "Top Up" their 
 * account using realex, paypal and/or wiretransfer. 
 *
 * Optional, is check required by admin to verify each transaction.
 *
 * */

namespace trz;

class Model_Transaction extends \Model_Table {
    public $table = "transaction";
    function init(){
        parent::init();
        $this->addField("name")->required("Reason for transaction");
        $this->addField("amount")->datatype("money")->required("Must be filled");
        $this->addField("is_completed")
            ->datatype("boolean")
            ->enum(array("Y","N"));
        $this->addField("is_verified")
            ->datatype("boolean")
            ->enum(array("Y","N"));
        $this->addField("method")->required("Must be indicated");
        $this->addField("aux");
        $this->addField("info");

        $this->add("trz/Controller_Audit");
    }
    function prepare($amount, $reason, $method, $aux){
        $this->set(array(
            "name" => $reason,
            "amount" => $amount,
            "is_completed" => false,
            "is_verified" => false,
            "method" => $method,
            "aux" => $aux
        ))->save();
    }
}
