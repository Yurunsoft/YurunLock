<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
function canTest($type)
{
	return !is_file($type . 'test.redis') || file_get_contents($type . 'test.redis') < 10;
}
function test($type)
{
	usleep(mt_rand(10, 500) * 1000); // 延迟时间随机，模拟更新token时的网络响应时间
	if(is_file($type . 'test.redis'))
	{
		file_put_contents($type . 'test.redis', file_get_contents($type . 'test.redis') + 1);
	}
	else
	{
		file_put_contents($type . 'test.redis', '1');
	}
}

// 测试结果为文件中内容为10为正确

// 并发测试-传统加锁(test.redis)
$lock2 = new \Yurun\Until\Lock\Redis('test2');
if(canTest(''))
{
	$lock2->lock();
	test('');
	$lock2->unlock();
}

// 并发测试-并发判断回调(callbacktest.redis)
$lock1 = new \Yurun\Until\Lock\Redis('test1');
if(\Yurun\Until\Lock\LockConst::LOCK_RESULT_CONCURRENT_UNTREATED === $lock1->lock(function(){
	return !canTest('callback');
}))
{
	test('callback');
}
$lock1->unlock();