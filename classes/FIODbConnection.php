<?php

namespace Yale\Yes3Fips;

use Exception;
use Yale\Yes3Fips\FIPS;
use Yale\Yes3Fips\Yes3;
use mysqli;

/**
 * Singleton class to hold the db connection.
 * 
 * Final keyword prevents the connection class from being extended
 */
final class FIODbConnection
{
   private static $instance; // instantiated connection object
   private $conn;

   /**
    * Null __construct() because no params called
    */
   private function __construct()
   {
   }

   /**
    * Null clone callback to prevent the instance from being cloned (which would create a second instance of it)
    */
   private function __clone()
   {
   }

   /**
    * Disable the "magic methods" __sleep() and __wakeup() to prevent serializing and unserializing.
    * (storing instance as serialized string for later restoration, which would create a second instance)
    */
   public function __sleep()
   {
      throw new Exception("Cannot serialize singleton");
   }
   
   public function __wakeup()
   {
      throw new Exception("Cannot unserialize singleton");
   }   

    public static function getInstance()
    {
        if (self::$instance == null) {
            $className = __CLASS__;
            self::$instance = new $className();
            self::initConn();
        }

        return self::$instance;
    }

    private static function initConn(){

        $host = ""; $user = ""; $password = ""; $database = "";

        $specfile = FIPS::getProjectSetting('db-spec-file');

        require $specfile;

        self::$instance->conn = new mysqli($host, $user, $password, $database);

        if (self::$instance->conn->connect_errno) {
            throw new Exception("Failed to connect to MySQL: (" . self::$instance->conn->connect_errno . ") " . self::$instance->conn->connect_error);
        }

    }

    public static function getConn()
    {
        $db = self::getInstance();

        return $db->conn;
    }

}

?>