<?php
namespace Zwei\RabbitMqEvent\Queue;

use Zwei\RabbitMqEvent\Exception\ParamsException;

/**
 * ���лص����
 *
 * Class CallbackResult
 * @package Zwei\RabbitMqEvent\Queue
 */
class CallbackResult
{
    /**
     * ����״̬��
     * @var string
     */
    protected $code = '00000';

    /**
     * ��������
     * @var array
     */
    protected $data = [];
    /**
     * ������Ϣ
     * @var string
     */
    protected $message = "";

    /**
     * ���췽����ʼ��
     *
     * CallbackResult constructor.
     * @param string $code ����״̬��
     * @param array $data ����ֵ
     * @param string $message ������Ϣ
     */
    public function __construct($code, array $data, $message)
    {
        $this->setCode($code);
        $this->setData($data);
        $this->setMessage($message);
    }

    /**
     * ��ȡ״̬��
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
     * ����״̬��
     * @param string $code �ַ�������
     * @throws ParamsException ���������׳��쳣
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
     * ���÷�������
     * @param array $data ��������
     * @throws ParamsException ���������׳��쳣
     */
    public function setData($data)
    {
        if (!is_array($data)) {
            throw new ParamsException("The argument must be an array");
        }
        $this->data = $data;
    }

    /**
     * ��ȡ��Ϣ
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * ������Ϣ
     * @param string $message ��Ϣ
     * @throws ParamsException ���������׳��쳣
     */
    public function setMessage($message)
    {
        if (!is_string($message)) {
            throw new ParamsException("The argument must be a string");
        }
        $this->message = $message;
    }


}