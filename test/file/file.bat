@echo off
del test.file
del callbacktest.file
for /l %%i in (1,1,20) do (start /b php file.php)
echo "OK"
pause