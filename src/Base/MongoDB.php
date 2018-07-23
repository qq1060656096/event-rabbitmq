<?php
namespace Zwei\RabbitMqEvent\Base;


/**
 * mongodb数据操作
 *
 * Class MongoDB
 * @package Zwei\RabbitMqEvent\Base
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
        $config = RabbitMqConfig::getCommon('mongodb');

        $dbName = $config['dbname'];
        $hosts  = $config['hosts'];
        $hosts  = implode(',', $hosts);

        $options = array(
            'connect'           => true,
            'connectTimeoutMS'  => 3000000,
            'db'                => $dbName
        );
        if ($config['is_auth']) {
            $user = $config['DemoUserRegister'];
            $pass = $config['pass'];
            $server = "mongodb://{$user}:{$pass}@{$hosts}";
        } else {
            $server = "mongodb://{$hosts}";
        }
//        var_dump($server);
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
        if ($mongoDb) {
            return $mongoDb;
        }
        $mongoDb = new MongoDB();
        return $mongoDb;
    }

    /**
     * 获取连接实例
     * @return \MongoClient
     */
    public function getDB()
    {
        return $this->db;
    }

    /**
     * 获取连接数据名
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * 插入集合数据
     *
     * @param string $collectionName 集合名
     * @param array $data 数据
     * @return array|bool
     */
    public function insert($collectionName, array $data)
    {

        $collection = $this->db->selectCollection($this->dbName, $collectionName);
        $options    = [
            'w' => 1, // 设置写入需要ack确认
        ];
        return $collection->insert($data, $options);
    }

    /**
     * 保存数据到集合
     *
     * @param string $collectionName 集合名
     * @param array $data 保存数据
     * @param array $where where条件
     * @return bool
     */
    public function update($collectionName, array $data, array $where)
    {
        $collection = $this->db->selectCollection($this->dbName, $collectionName);
        return $collection->update($where, $data);
    }

}
