<?php
namespace Yurun\Until\Lock;

$ref = new \ReflectionClass('\Memcache');
var_dump($ref->getMethods());
exit;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
function canTest($type)
{
	return !is_file($type . 'test.memcache') || file_get_contents($type . 'test.memcache') < 10;
}
function test($type)
{
	usleep(mt_rand(10, 500) * 1000); // 延迟时间随机，模拟更新token时的网络响应时间
	if(is_file($type . 'test.memcache'))
	{
		file_put_contents($type . 'test.memcache', file_get_contents($type . 'test.memcache') + 1);
	}
	else
	{
		file_put_contents($type . 'test.memcache', '1');
	}
}

// 测试结果为文件中内容为10为正确

// 并发测试-传统加锁(test.memcache)
$lock2 = new \Yurun\Until\Lock\Memcache('test2', [
	// 'host'		=>	'127.0.0.1', // redis服务器地址
	// 'port'		=>	6379,		 // 端口
	// 'timeout'	=>	0,			 // 超时时间
	// 'pconnect'	=>	false,		 // 是否持久化连接
	// 'username'	=>	null,		 // 用户名
	// 'password'	=>	null,		 // 密码
]);
if(canTest(''))
{
	if(LockConst::LOCK_RESULT_SUCCESS === $lock2->lock())
	{
		test('');
		$lock2->unlock();
	}
}

// 并发测试-并发判断回调(callbacktest.memcache)
$lock1 = new \Yurun\Until\Lock\Memcache('test1');
$result = $lock1->lock(
	function(){
		// 加锁后处理的任务
		test('callback');
	},
	function(){
		// 判断是否其它并发已经处理过任务
		return !canTest('callback');
	}
);
switch($result)
{
	case LockConst::LOCK_RESULT_CONCURRENT_COMPLETE:
		// 其它请求已处理
		break;
	case LockConst::LOCK_RESULT_CONCURRENT_UNTREATED:
		// 在当前请求处理
		break;
	case LockConst::LOCK_RESULT_FAIL:
		echo '获取锁失败', PHP_EOL;
		break;
}