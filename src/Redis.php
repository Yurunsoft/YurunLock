<?php
namespace Yurun\Until\Lock;

class Redis extends Base
{
	/**
	 * 等待锁超时时间，单位：毫秒，0为不限制
	 * @var int
	 */
	public $waitTimeout;

	/**
	 * 获得锁每次尝试间隔，单位：毫秒
	 * @var int
	 */
	public $waitSleepTime;

	/**
	 * 锁超时时间，单位：秒
	 * @var int
	 */
	public $lockExpire;

	/**
	 * Redis操作对象
	 * @var Redis
	 */
	public $handler;

	public $guid;

	public $lockValue;

	/**
	 * 构造方法
	 * @param string $name 锁名称
	 * @param array $params redis连接参数
	 * @param integer $waitTimeout 获得锁等待超时时间，单位：毫秒，0为不限制
	 * @param integer $waitSleepTime 获得锁每次尝试间隔，单位：毫秒
	 * @param integer $lockExpire 锁超时时间，单位：秒
	 */
	public function __construct($name, $params = array(), $waitTimeout = 0, $waitSleepTime = 1, $lockExpire = 3)
	{
		parent::__construct($name, $params);
		if(!class_exists('Redis'))
		{
			throw new Exception('未找到 Redis 扩展', LockConst::EXCEPTION_EXTENSIONS_NOT_FOUND);
		}
		$this->waitTimeout = $waitTimeout;
		$this->waitSleepTime = $waitSleepTime;
		$this->lockExpire = $lockExpire;
		$host = isset($option['host']) ? $option['host'] : '127.0.0.1';
		$port = isset($option['port']) ? $option['port'] : 6379;
		$timeout = isset($option['timeout']) ? $option['timeout'] : 0;
		$pconnect = isset($option['pconnect']) ? $option['pconnect'] : false;
		$this->handler = new \Redis;
		if($pconnect)
		{
			$this->handler->pconnect($host, $port, $timeout);
		}
		else
		{
			$this->handler->connect($host, $port, $timeout);
		}
		$this->guid = uniqid('', true);
	}

	/**
	 * 加锁
	 * @return bool
	 */
	protected function __lock()
	{
		while(true)
		{
			$value = json_decode($this->handler->get($this->name), true);
			$this->lockValue = array(
				'expire'	=>	time() + $this->lockExpire,
				'guid'		=>	$this->guid,
			);
			if(null === $value)
			{
				// 无值
				$result = $this->handler->setnx($this->name, json_encode($this->lockValue));
				if($result)
				{
					$this->handler->expire($this->name, $this->lockExpire);
					return true;
				}
			}
			else
			{
				// 有值
				if($value['expire'] < time())
				{
					$result = json_decode($this->handler->getSet($this->name, json_encode($this->lockValue)), true);
					if($result === $value)
					{
						$this->handler->expire($this->name, $this->lockExpire);
						return true;
					}
				}
			}
			usleep($this->waitSleepTime * 1000);
		}
		return false;
	}

	/**
	 * 释放锁
	 * @return bool
	 */
	protected function __unlock()
	{
		if((isset($this->lockValue['expire']) && $this->lockValue['expire'] > time()))
		{
			return $this->handler->del($this->name) > 0;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 不阻塞加锁
	 * @return bool
	 */
	protected function __unblockLock()
	{
		$value = json_decode($this->handler->get($this->name), true);
		$this->lockValue = array(
			'expire'	=>	time() + $this->lockExpire,
			'guid'		=>	$this->guid,
		);
		if(null === $value)
		{
			// 无值
			$result = $this->handler->setnx($this->name, $this->lockValue);
			if(!$result)
			{
				return false;
			}
		}
		else
		{
			// 有值
			if($value < time())
			{
				$result = json_decode($this->handler->getSet($this->name, json_encode($this->lockValue)), true);
				if($result !== $value)
				{
					return false;
				}
			}
		}
		return true;
	}
}