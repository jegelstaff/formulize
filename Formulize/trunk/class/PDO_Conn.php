<?php 
include 'PDO_Config.php';
////Connection Using PDO instead of Mysql Query Who Uses mysql query now?????????????????//To Avoid any MYSQL PHP TIME out issues in the future ///// Noe one
class Connection {
    private $conn = null;
    public function Connect()
    {
        try {
            $this->conn= new \PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //$this->conn->set_limit_time(60);
            $TestConn="Connected Successfully";
            //echo $TestConn ;
        }
        catch (\PDOException $e) {
            die('Failed to Connect' . $e->getMessage());
        }
        return $this->conn;
    }

}

//echo "Test Connection";
// $connection = (new Connection())->connect();



