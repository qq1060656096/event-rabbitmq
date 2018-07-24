<?php
namespace Zwei\RabbitMqEvent\Queue;

use Zwei\RabbitMqEvent\Exception\ParamsException;

/**
 * 队列回调结果
 *
 * Class CallbackResult
 * @package Zwei\RabbitMqEvent\Queue
 */
class CallbackResult
{
    /**
     * 返回状态码
     * @var string
     */
    protected $code = '00000';

    /**
     * 返回数据
     * @var array
     */
    protected $data = [];
    /**
     * 设置消息
     * @var string
     */
    protected $message = "";

    /**
     * 构造方法初始化
     *
     * CallbackResult constructor.
     * @param string $code 返回状态码
     * @param array $data 返回值
     * @param string $message 返回消息
     */
    public function __construct($code, array $data, $message)
    {
        $this->setCode($code);
        $this->setData($data);
        $this->setMessage($message);
    }

    /**
     * 获取状态码
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */

    /**
     * 设置状态码
     * @param string $code 字符串整数
     * @throws ParamsException 参数错误抛出异常
     */
    public function setCode($code)
    {
        if (!is_string($code) || !is_numeric($code)) {
            throw new ParamsException("The argument must be a string");
        }
        $this->code = $code;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * 设置返回数据
     * @param array $data 返回数据
     * @throws ParamsException 参数错误抛出异常
     */
    public function setData($data)
    {
        if (!is_array($data)) {
            throw new ParamsException("The argument must be an array");
        }
        $this->data = $data;
    }

    /**
     * 获取消息
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 设置消息
     * @param string $message 消息
     * @throws ParamsException 参数错误抛出异常
     */
    public function setMessage($message)
    {
        if (!is_string($message)) {
            throw new ParamsException("The argument must be a string");
        }
        $this->message = $message;
    }


}