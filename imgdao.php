<?php
require_once "img-config.php";

class Connection {
    protected $connection;
    private string $host = MYSQL_URL;
    private string $user = MYSQL_USER;
    private string $pass = MYSQL_PASS;
    private string $db = MYSQL_DB;

    public function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->db);
    }

    public function query(string $sql) {
        return $this->connection->query($sql);
    }
}

class ImageObject extends Connection {
    public function __construct() {
        parent::__construct();
        parent::query('CREATE TABLE IF NOT EXISTS img (
            img_id INT AUTO_INCREMENT PRIMARY KEY,
            caption VARCHAR(255),
            uuid CHAR(40),
            file_path VARCHAR(512),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=INNODB;');
    }

    public function saveToDatabase(string $uuid, string $file):string|null {
        $query_prepared = $this->connection->stmt_init();
        if ($query_prepared->prepare('INSERT INTO ' . MYSQL_IMG_TABLE . '(uuid, file_path) VALUES(?, ?);')) {
            $query_prepared->bind_param('ss',$uuid, $file);
            $query_prepared->execute();
            $query_prepared->close();
            return null;
        } else {
            return "Internal error while saving to database.";
        }
    }

    public function queryDatabase(string $uuid):array {
        $query_prepared = $this->connection->stmt_init();
        if ($query_prepared->prepare('SELECT file_path FROM ' . MYSQL_IMG_TABLE . ' WHERE sha1 = ? LIMIT 1;')) {
            $query_prepared->bind_param('s', $uuid);
            $query_prepared->execute();
            $query_prepared->bind_result($filepath);

            if ($query_prepared->fetch()) {
                $ret = array($filepath, null);
            } else {
                $ret = array(null, "Cannot find requested image");
            }

            $query_prepared->close();
            return $ret;
        } else {
            return array(null, "Internal error while saving to database.");
        }
    }
}
