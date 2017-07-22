<?php
namespace Yurun\Until\Lock;

class Memcache extends Base
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
	 * Memcache操作对象
	 * @var \Memcache
	 */
	public $handler;

	public $guid;

	public $lockValue;

	/**
	 * 构造方法
	 * @param string $name 锁名称
	 * @param array $params 连接参数
	 * @param integer $waitTimeout 获得锁等待超时时间，单位：毫秒，0为不限制
	 * @param integer $waitSleepTime 获得锁每次尝试间隔，单位：毫秒
	 * @param integer $lockExpire 锁超时时间，单位：秒
	 */
	public function __construct($name, $params = array(), $waitTimeout = 0, $waitSleepTime = 1, $lockExpire = 3)
	{
		parent::__construct($name, $params);
		if(!class_exists('\Memcache'))
		{
			throw new Exception('未找到 Memcache 扩展', LockConst::EXCEPTION_EXTENSIONS_NOT_FOUND);
		}
		$this->waitTimeout = $waitTimeout;
		$this->waitSleepTime = $waitSleepTime;
		$this->lockExpire = $lockExpire;
		if($params instanceof \Memcache)
		{
			$this->handler = $params;
			$this->isInHandler = true;
		}
		else
		{
			$host = isset($params['host']) ? $params['host'] : '127.0.0.1';
			$port = isset($params['port']) ? $params['port'] : 11211;
			$timeout = isset($params['timeout']) ? $params['timeout'] : 120;
			$pconnect = isset($params['pconnect']) ? $params['pconnect'] : false;
			$this->handler = new \Memcache;
			if($pconnect)
			{
				$this->handler->pconnect($host, $port, $timeout);
			}
			else
			{
				$this->handler->connect($host, $port, $timeout);
			}
		}
		$this->guid = uniqid('', true);
	}

	/**
	 * 加锁
	 * @return bool
	 */
	protected function __lock()
	{
		$time = microtime(true);
		$sleepTime = $this->waitSleepTime * 1000;
		$waitTimeout = $this->waitTimeout / 1000;
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
				$result = $this->handler->add($this->name, json_encode($this->lockValue), 0, $this->lockExpire);
				if($result)
				{
					return true;
				}
			}
			else
			{
				// 有值
				if($value['expire'] < time())
				{
					$result = $this->handler->add($this->name, json_encode($this->lockValue), 0, $this->lockExpire);
					if($result)
					{
						return true;
					}
				}
			}
			if(0 === $this->waitTimeout || microtime(true) - $time < $waitTimeout)
			{
				usleep($sleepTime);
			}
			else
			{
				break;
			}
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
			return $this->handler->delete($this->name) > 0;
		}
		else
		{
			return true;
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
			$result = $this->handler->add($this->name, json_encode($this->lockValue), 0, $this->lockExpire);
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
				$result = $this->handler->add($this->name, json_encode($this->lockValue), 0, $this->lockExpire);
				if(!$result)
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 关闭锁对象
	 * @return bool
	 */
	protected function __close()
	{
		if(null !== $this->handler)
		{
			$result = $this->handler->close();
			$this->handler = null;
			return $result;
		}
	}
}