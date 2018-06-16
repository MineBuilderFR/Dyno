@echo off
TITLE Dyno Packet Manager
cd /d %~dp0

if exist bin\php\php.exe (
	set PHPRC=""
	set PHP_BINARY=bin\php\php.exe
) else (
	set PHP_BINARY=php
)

if exist Dyno*.phar (
	set DYNO_FILE=Dyno*.phar
) else (
	if exist src\dyno\Boot.php (
	    set DYNO_FILE=src\dyno\Boot.php
    ) else (
        if exist Dyno.phar (
           set DYNO_FILE=Dyno.phar
        ) else (
		    echo "[ERROR] Couldn't find a valid Dyno installation."
		    pause
		    exit 1
	    )
	)
)

if exist bin\mintty.exe (
	start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="Consolas" -o FontHeight=10 -o CursorType=0 -o CursorBlinks=1 -h error -t "Dyno" -w max %PHP_BINARY% %DYNO_FILE% --enable-ansi %*
) else (
	REM pause on exitcode != 0 so the user can see what went wrong
	%PHP_BINARY% -c bin\php %DYNO_FILE% %*
)