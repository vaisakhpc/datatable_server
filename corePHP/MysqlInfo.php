<?php
class MysqlInfo
{
    private $con;

    public function __construct()
    {
        require "./config/settings.ini.php";
        $this->setConnection($dbConfig);
    }

    public function setConnection($dbConfig)
    {
    	$this->con = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['db']);
    	/* check connection */
		if (mysqli_connect_errno()) {
		    printf("Connect failed: %s\n", mysqli_connect_error());
		    exit();
		}
    }

    public function getConnection()
    {
    	return $this->con;
    }
}
