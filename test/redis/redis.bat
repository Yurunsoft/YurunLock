@echo off
del test.redis
del callbacktest.redis
for /l %%i in (1,1,20) do (start /b php redis.php)
echo "OK"
pause