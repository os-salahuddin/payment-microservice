<?php
namespace app\components;
use Yii;
use yii\db\Exception;
use yii\helpers\Json;

class RedisHelper
{
    private static $instance;

    /**
     * @return RedisHelper
     */
    public static function getInstance(): RedisHelper
    {
        if(self::$instance === null)  {
            self::$instance = new self();
        }

        return self::$instance;
    }
    /**
     * @param $key
     * @param $field
     * @param $value
     * @param int $duration
     * @return array|bool|string|null
     * @throws Exception
     */
    public function hSet($key, $field, $value, int $duration = 3600)
    {
        return Yii::$app->redis->executeCommand('HSET', [$key, $field, Json::encode($value), 'EX', $duration]);
    }

    /**
     * @param $key
     * @return array|bool|string|null
     * @throws Exception
     */
    public function hGetAll($key)
    {
        return Yii::$app->redis->executeCommand('HGETALL', [$key]);
    }

    /**
     * @param string $pattern
     * @return array|bool
     * @throws Exception
     */
    public function keys($pattern)
    {
        return Yii::$app->redis->executeCommand('KEYS', [$pattern]);
    }

    /**
     * @param string $key
     @param string $field
     * @return array|bool
     * @throws Exception
     */
    public function hExists($key, $field)
    {
        return Yii::$app->redis->executeCommand('hExists', [$key, $field]);
    }

    /**
     * @param string $key
     * @return array|bool
     * @throws Exception
     */
    public function exists($key)
    {
        return Yii::$app->redis->executeCommand('exists', [$key]);
    }

    /**
     * @param string $key
     * @return int
     * @throws Exception
     */
    public function del($key)
    {
        return Yii::$app->redis->executeCommand('del', [$key]);
    }
}