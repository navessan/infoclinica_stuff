rem @echo off

PATH=%PATH%;c:\Program Files\Firebird\Firebird_3_0\

set zip="C:\Program Files\7-Zip\7z.exe"

rem gbak -b -v  localhost:e:\1000.gdb e:\1000.gbk -user SYSDBA -pass masterkey  -y backup.log

set dbuser=chea
set dbpass=PDNTP
set dbrole=ADMIN

set src=D:\data\backup\
set src=\\server\data\backups\ik-bd\
set dst2=\\server2\data\backups\ik-bd\

set "currentTime=%Time: =0%"
set my_date=%date:~-4%%date:~3,2%%date:~0,2%_%currentTime:~0,2%%currentTime:~3,2%%currentTime:~6,2%

rem c
rem set db=001
rem set file=clinic%db%_%my_date%.fbk
rem gbak -b -v  localhost:%db% %src%%file% -user %dbuser% -pass %dbpass% -y %src%clinic%db%_%my_date%.log
rem start "async zip" %zip% a -tzip %src%%file%.zip %src%%file%

rem tom
rem set db=002
rem set file=clinic%db%_%my_date%.fbk
rem gbak -b -v  ik-tom:%db% %src%%file% -user %dbuser% -pass %dbpass% -y %src%clinic%db%_%my_date%.log
rem start "async zip" %zip% a -tzip %src%%file%.zip %src%%file%

rem cbd
set db=099
set file=clinic%db%_%my_date%.fbk
 gbak -b -v  localhost:%db% %src%%file% -user %dbuser% -pass %dbpass% -y %src%clinic%db%_%my_date%.log
 start "async zip" %zip% a -tzip %src%%file%.zip %src%%file%

rem copy %src%%file% %dst2%

