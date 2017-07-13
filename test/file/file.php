<?php
namespace Yurun\Until\Lock;
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
function canTest($type)
{
	return !is_file($type . 'test.file') || file_get_contents($type . 'test.file') < 10;
}
function test($type)
{
	usleep(mt_rand(10, 500) * 1000); // 延迟时间随机，模拟更新token时的网络响应时间
	if(is_file($type . 'test.file'))
	{
		file_put_contents($type . 'test.file', file_get_contents($type . 'test.file') + 1);
	}
	else
	{
		file_put_contents($type . 'test.file', '1');
	}
}

// 测试结果为文件中内容为10为正确

// 并发测试-传统加锁(test.file)
$lock2 = new \Yurun\Until\Lock\File('test2');
if(canTest(''))
{
	if(LockConst::LOCK_RESULT_SUCCESS === $lock2->lock())
	{
		test('');
		$lock2->unlock();
	}
}

// 并发测试-并发判断回调(callbacktest.file)
$lock1 = new \Yurun\Until\Lock\File('test1');
switch($lock1->lock(function(){
	return !canTest('callback');
}))
{
	case LockConst::LOCK_RESULT_CONCURRENT_COMPLETE:
		// 其它请求已处理
		$lock1->unlock();
		break;
	case LockConst::LOCK_RESULT_CONCURRENT_UNTREATED:
		// 在当前请求处理
		test('callback');
		$lock1->unlock();
		break;
	case LockConst::LOCK_RESULT_FAIL:
		echo '获取锁失败', PHP_EOL;
		break;
}