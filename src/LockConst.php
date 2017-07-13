<?php
namespace Yurun\Until\Lock;

final class LockConst
{
	/**
	 * 获取锁失败
	 */
	const LOCK_RESULT_FAIL = 0;

	/**
	 * 成功获取到锁
	 */
	const LOCK_RESULT_SUCCESS = 1;

	/**
	 * 并发锁已处理
	 */
	const LOCK_RESULT_CONCURRENT_COMPLETE = 2;

	/**
	 * 并发锁未处理
	 */
	const LOCK_RESULT_CONCURRENT_UNTREATED = 3;

	/**
	 * 加锁文件打开失败
	 */
	const EXCEPTION_LOCKFILE_OPEN_FAIL = 10001;

	/**
	 * 已经加锁
	 */
	const EXCEPTION_ALREADY_LOCKED = 10002;

	/**
	 * 未加锁
	 */
	const EXCEPTION_UNLOCKED = 10003;

	/**
	 * 未找到扩展
	 */
	const EXCEPTION_EXTENSIONS_NOT_FOUND = 10004;
}