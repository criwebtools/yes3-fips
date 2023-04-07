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
 * 
 * @package 
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
   //public function __sleep()
   //{
   //   throw new Exception("Cannot serialize singleton");
   //}
   
   public function __wakeup()
   {
      throw new Exception("Cannot unserialize singleton");
   }   

   public static function getInstance()
   {
      if (self::$instance == null) {
         $className = __CLASS__;
         self::$instance = new $className();
      }
      return self::$instance;
   }

   public static function initializeConnection()
   {
      $db = self::getInstance();

      $host = ""; $user = ""; $password = ""; $database = "";

      $specfile = FIPS::getProjectSetting('db-spec-file');

      require $specfile; // connection info, hopefully store off webroot

      $db->conn = new mysqli($host, $user, $password, $database);

      if ($db->conn->connect_errno) {
         throw new Exception("Failed to connect to MySQL: (" . $db->conn->connect_errno . ") " . $db->conn->connect_error);
      }
            
      Yes3::logDebugMessage(0, 'Db connection established', 'FIODbConnection');
   }

   /*
   public static function getInstance()
   {
      if (self::$instance == null) {

         $className = __CLASS__;

         self::$instance = new $className();

         $host = "";
         $database = "";
         $password = "";
         $user = "";

         $specfile = FIPS::getProjectSetting('db-spec-file');

         //Yes3::logDebugMessage(0, $specfile, 'FIODbConnection:specfile');

         //require_once $specfile;

         
         $host = FIPS::getProjectSetting('db-host'); 
         $user = FIPS::getProjectSetting('db-user'); 
         $password = FIPS::getProjectSetting('db-password'); 
         $database = FIPS::getProjectSetting('db-database');
         
         self::$instance->conn = new mysqli($host, $user, $password, $database);

         if (self::$instance->conn->connect_errno) {

            throw new Exception("Failed to connect to MySQL: (" . self::$instance->conn->connect_errno . ") " . self::$instance->conn->connect_error);
         }

         Yes3::logDebugMessage(0, 'Db connection established', 'FIODbConnection');
      }

      return self::$instance;
   }
   */
   /*
   public static function initializeConnection()
   {
      $db = self::getInstance();

      $host = FIPS::getProjectSetting('db-host'); 
      $user = FIPS::getProjectSetting('db-user'); 
      $password = FIPS::getProjectSetting('db-password'); 
      $database = FIPS::getProjectSetting('db-database');

      $db->conn = new mysqli($host, $user, $password, $database);

      if ($db->conn->connect_errno) {
         throw new Exception("Failed to connect to MySQL: (" . $db->conn->connect_errno . ") " . $db->conn->connect_error);
      }
   }
*/
   public static function getConn()
   {
      Yes3::logDebugMessage(0, print_r($GLOBALS['fips_db_conn'], true), 'FIODbConnection:pre');
      
      if ( !isset($GLOBALS['fips_db_conn']) || !is_object($GLOBALS['fips_db_conn']) || !$GLOBALS['fips_db_conn']->host_info) {

         $host = ""; $user = ""; $password = ""; $database = "";

         $specfile = FIPS::getProjectSetting('db-spec-file');
 
         require $specfile; // connection info, hopefully store off webroot
 
         $GLOBALS['fips_db_conn'] = new mysqli($host, $user, $password, $database);
 
         if ( $GLOBALS['fips_db_conn']->connect_errno) {

            Yes3::logDebugMessage(0,  $GLOBALS['fips_db_conn']->connect_error, 'FIODbConnection');
            throw new Exception("Failed to connect to MySQL: (" .  $GLOBALS['fips_db_conn']->connect_errno . ") " .  $GLOBALS['fips_db_conn']->connect_error);
         }
         else {

            Yes3::logDebugMessage(0, 'Db connection established', 'FIODbConnection');
         }
     }
         
     Yes3::logDebugMessage(0, print_r($GLOBALS['fips_db_conn'], true), 'FIODbConnection:post');
 
      return $GLOBALS['fips_db_conn'];
      
      //global $fips_db_conn;

      //return $fips_db_conn;
      /*
      $db = self::getInstance();

      if ( !$db->conn ){

         self::initializeConnection();
         $db = self::getInstance();
      }
      return $db->conn;
      */
   }

}

?>