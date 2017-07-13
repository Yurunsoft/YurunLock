@echo off
del test.memcache
del callbacktest.memcache
for /l %%i in (1,1,20) do (start /b php memcache.php)
echo "OK"
pause