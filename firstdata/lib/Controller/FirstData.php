<?php
namespace firstdata;

class Controller_FirstData extends \AbstractController {
    /**
    * @author First Data Latvia
    * @version 1.0
    * @package sample
    */


    /**
    * Package
    * @package sample
    * @subpackage classes
    */

    /**
     * Variable $url
     * @access private
     * @var string
     */
    var $url;
     /**
     * Variable $keystore
     * @access private
     * @var string
     */
    var $keystore;
    
    /**
     * Variable $keystorepassword
     * @access private
     * @var string
     */
    var $keystorepassword;

    /**
     * Variable $verbose
     * @access private
     * @var boolean
     */
    var $verbose;


    /**
     * Constructor sets up {$link, $keystore, $keystorepassword, $verbose}
     * @param string $url url to declare
     * @param string $keystore value of the keystore
     * @param string $keystorepassword value of the keystorepassword
     * @param boolean $verbose TRUE to output verbose information. Writes output to STDERR, or the file specified using CURLOPT_STDERR.
    */
    function init() {
        parent::init();
        $this->url = $this->api->getConfig("firstdata/server_url");
        $this->keystore = $this->api->getConfig("firstdata/cert_url");
        $this->keystorepassword = $this->api->getConfig("firstdata/cert_pass");
        $this->verbose = $this->api->getConfig("firstdata/verbose");
        $this->checkDB();
    }


    /**
    * Send parameters
    * 
    * @param array post parameters
    * @return string result
    */
    function sendPost($params){

        if(!file_exists($this->keystore)){
            $result = "file " . $this->keystore . " not exists";
            error_log($result);
            return $result;
        }
        
        if(!is_readable($this->keystore)){
        $result = "Please check CHMOD for file \"" . $this->keystore . "\"! It must be readable!";
            error_log($result);
            return $result;
        }

        $post = "";
        
        foreach ($params as $key => $value){
            $post .= "$key=$value&";
        }


        $curl = curl_init();
        if($this->verbose){
            curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
        }
        
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        //curl_setopt($curl, CURLOPT_SSLVERSION,2);
        curl_setopt($curl, CURLOPT_SSLCERT, $this->keystore);
        curl_setopt($curl, CURLOPT_CAINFO, $this->keystore);
        curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $this->keystorepassword);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result =curl_exec ($curl);

            if(curl_error($curl)){
            $result = curl_error($curl);
            error_log($result);
        }
        curl_close ($curl);
        return $result;
    }



    /**
    * Registering of SMS transaction
    * @param int $amount transaction amount in minor units, mandatory 
    * @param int $currency transaction currency code, mandatory 
    * @param string $ip clients IP address, mandatory
    * @param string $desc description of transaction, optional
    * @param string $language authorization language identificator, optional 
    * @return string TRANSACTION_ID
    */

    function startSMSTrans($amount, $currency, $ip, $desc, $language){

        $params = array(
            'command' => 'v',
            'amount'  => $amount,
            'currency'=> $currency,
            'client_ip_addr'      => $ip,
            'description'    => $desc,
            'language'=> $language	  
        );
        return $this->sendPost($params);
    }

    /**
    * Registering of DMS authorisation
    * @param int $amount transaction amount in minor units, mandatory 
    * @param int $currency transaction currency code, mandatory 
    * @param string $ip clients IP address, mandatory
    * @param string $desc description of transaction, optional
    * @param string $language authorization language identificator, optional 
    * @return string TRANSACTION_ID
    */

    function startDMSAuth($amount, $currency, $ip, $desc, $language){

        $params = array(
            'command' => 'a',
            'msg_type'=> 'DMS',
            'amount'  => $amount,
            'currency'=> $currency,
            'client_ip_addr'      => $ip,
            'description'    => $desc,
            'language'    => $language,  
        );
        return $this->sendPost($params);
    }

    /**
    * Making of DMS transaction
    * @param int $auth_id id of previously made successeful authorisation
    * @param int $amount transaction amount in minor units, mandatory 
    * @param int $currency transaction currency code, mandatory 
    * @param string $ip clients IP address, mandatory
    * @param string $desc description of transaction, optional
    * @return string RESULT, RESULT_CODE, RRN, APPROVAL_CODE
    */

    function makeDMSTrans($auth_id, $amount, $currency, $ip, $desc, $language){

        $params = array(
            'command' => 't',
            'msg_type'=> 'DMS',
            'trans_id' => $auth_id, 
            'amount'  => $amount,
            'currency'=> $currency,
            'client_ip_addr' => $ip	
        );

        $str = $this->sendPost($params);
        return $str;
    }

    /**
    * Transaction result
    * @param int $trans_id transaction identifier, mandatory
    * @param string $ip clients IP address, mandatory
    * @return string RESULT, RESULT_CODE, 3DSECURE, AAV, RRN, APPROVAL_CODE
    */

    function getTransResult($trans_id, $ip){

        $params = array(
            'command' => 'c',
            'trans_id' => $trans_id, 
            'client_ip_addr'      => $ip
        );

        $str = $this->sendPost($params);
        return $str;
    }

    /**
    * Transaction reversal
    * @param int $trans_id transaction identifier, mandatory
    * @param int $amount transaction amount in minor units, mandatory 
    * @return string RESULT, RESULT_CODE
    */

    function reverse($trans_id, $amount){

        $params = array(
            'command' => 'r',
            'trans_id' => $trans_id, 
            'amount'      => $amount
        );

        $str = $this->sendPost($params);
        return $str;
    }

    /**
    * Closing of business day
    * @return string RESULT, RESULT_CODE, FLD_075, FLD_076, FLD_087, FLD_088
    */

    function closeDay(){
        $params = array(
                'command' => 'b',
            );
            
        $str = $this->sendPost($params);
        return $str;

    }
    function checkDB(){
        $this->db = $this->add('DB')->connect($this->api->getConfig("firstdata/dsn"));
        $db_table_transaction = $this->api->getConfig("firstdata/table/transaction");
        $db_table_batch = $this->api->getConfig("firstdata/table/batch");
        $db_table_error = $this->api->getConfig("firstdata/table/error");

        $this->db->query("CREATE TABLE /*!32312 IF NOT EXISTS*/ $db_table_transaction  (
            id INT(10) NOT NULL AUTO_INCREMENT,
            trans_id VARCHAR(50),
            amount INT(10),
            currency INT(10),
            client_ip_addr VARCHAR(50),
            description TEXT,
            language VARCHAR(50),
            dms_ok VARCHAR(50),
            result VARCHAR(50),
            result_code VARCHAR(50),
            result_3dsecure VARCHAR(50),
            card_number VARCHAR(50),
            t_date VARCHAR(20),
            response TEXT,
            reversal_amount INT(10),
            makeDMS_amount INT(10),
            PRIMARY KEY (id)
        )");
        $this->db->query("CREATE TABLE /*!32312 IF NOT EXISTS*/ $db_table_batch  (
            id INT(10) NOT NULL AUTO_INCREMENT,
            result TEXT,
            result_code VARCHAR(3),
            count_reversal VARCHAR(10),
            count_transaction VARCHAR(10),
            amount_reversal VARCHAR(16),
            amount_transaction VARCHAR(16),
            close_date VARCHAR(20),
            response TEXT,
            PRIMARY KEY (id)
        )");
        $this->db->query("CREATE TABLE /*!32312 IF NOT EXISTS*/ $db_table_error  (
            id INT(10) NOT NULL AUTO_INCREMENT,
            error_time VARCHAR (20),
            action VARCHAR (20),
            response TEXT,
            PRIMARY KEY (id)
        )");
    }
}
