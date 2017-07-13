@echo off
del test.memcached
del callbacktest.memcached
for /l %%i in (1,1,20) do (start /b php memcached.php)
echo "OK"
pause