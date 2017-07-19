# YurunLock
PHP阻塞锁和非阻塞锁机制，内置解决并发锁重复执行的方案。目前支持文件、Redis、Memcache、Memcached。

# composer安装

在你项目中的 `composer.json` 文件中加入下面的内容
```json
{
    "require": {
        "yurunsoft/yurun-lock": "dev-master"
    }
}
```

中国用户如果使用composer很卡，请查看：https://pkg.phpcomposer.com/

# 代码用法

## 文件锁

```php
<?php
$lock = new \Yurun\Until\Lock\File('我是锁名称');

$lock->lock(); // 阻塞锁
// TODO:在这里做你的一些事情
$lock->unlock(); // 解锁

// 带回调的阻塞锁，防止并发锁处理重复执行
result = $lock->lock(
	function(){
		// TODO:在这里做你的加锁后处理的任务

	},
	function(){
		// 判断是否其它并发已经处理过任务
		return false;
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
		// 获取锁失败
		break;
}

// 不阻塞锁，获取锁失败就返回false
if($lock->unblockLock())
{
	// TODO:在这里做你的一些事情
}
else
{
	// 获取锁失败
}
```

## redis/memcache/memcached锁

```php
<?php
$lock = new \Yurun\Until\Lock\Redis(	// 可以把Redis替换成Memcache/Memcached，下面代码用法相同
	'我是锁名称',
	array(
		'host'		=>	'127.0.0.1',
		'port'		=>	11211,
		'timeout'	=>	0,
		'pconnect'	=>	false,
	), // 连接配置，留空则为默认值
	0, // 获得锁等待超时时间，单位：毫秒，0为不限制，留空则为默认值
	1, // 获得锁每次尝试间隔，单位：毫秒，留空则为默认值
	3, // 锁超时时间，单位：秒，留空则为默认值
);

$lock->lock(); // 阻塞锁
// TODO:在这里做你的一些事情
$lock->unlock(); // 解锁

// 带回调的阻塞锁，防止并发锁处理重复执行
result = $lock->lock(
	function(){
		// TODO:在这里做你的加锁后处理的任务

	},
	function(){
		// 判断是否其它并发已经处理过任务
		return false;
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
		// 获取锁失败
		break;
}

// 不阻塞锁，获取锁失败就返回false
if($lock->unblockLock())
{
	// TODO:在这里做你的一些事情
}
else
{
	// 获取锁失败
}
```

## 直接传入操作对象

直接传入操作对象支持文件、redis、memcache、memcached。可以防止重复实例化对象，造成内存和网络负担。

```php
// 文件
$fp = fopen('1.txt', 'w+');
$lock = new \Yurun\Until\Lock\File('我是锁名称', $fp);
$lock->lock();
// 做一些事情
$lock->unlock();
fclose($fp);

// redis、memcache、memcached同理
$redis = new \Redis;
$redis->connect($host, $port, $timeout);
$lock = new \Yurun\Until\Lock\Redis('我是锁名称', $$redis);
$lock->lock();
// 做一些事情
$lock->unlock();
redis->close();
```