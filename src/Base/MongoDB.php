<?php
namespace Zwei\EventRabbitMQ\Base;


/**
 * mongodb数据操作
 *
 * Class MongoDB
 * @package Zwei\EventRabbitMQ\Base
 */
class MongoDB
{
    /**
     * @var \MongoClient
     */
    private $db = null;

    private $dbName = null;

    private function __construct()
    {
        $config = RabbitMqConfig::getCommon('MONGODB');

        $dbName = $config['dbname'];
        $hosts  = $config['hosts'];
        $hosts  = implode(',', $hosts);

        $options = array(
            'connect'           => true,
            'connectTimeoutMS'  => 3000000,
            'db'                => $dbName
        );
        if ($config['isAuth']) {
            $user = $config['user'];
            $pass = $config['pass'];
            $server = "mongodb://{$user}:{$pass}@{$hosts}";
        } else {
            $server = "mongodb://{$hosts}";
        }
        $this->db       = new \MongoClient($server);
        $this->dbName   = $dbName;
    }

    /**
     * 获取实例
     * @return MongoDB
     */
    public static function getInstance()
    {
        static $mongoDb = null;
        if (!$mongoDb) {
            return $mongoDb;
        }
        $mongoDb = new MongoDB();
        return $mongoDb;
    }

    /**
     * 插入集合数据
     *
     * @param string $tableName 集合名
     * @param array $data 数据
     * @return array|bool
     */
    public function insert($tableName, array $data)
    {

        $collection = $this->db->selectCollection($this->dbName, $tableName);
        $options    = [
            'w' => 1, // 设置写入需要ack确认
        ];
        return $collection->insert($data, $options);
    }
    
}