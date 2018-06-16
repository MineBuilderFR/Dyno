    [CmdletBinding(PositionalBinding = $false)]
    param (
        [string]$php = "",
        [switch]$Loop = $false,
        [string]$file = "",
        [string][Parameter(ValueFromRemainingArguments)]$extraDynoArgs
    )

    if ($php -ne "")
    {
        $binary = $php
    }
    elseif (Test-Path "bin\php\php.exe")
    {
        $env:PHPRC = ""
        $binary = "bin\php\php.exe"
    }
    else
    {
        $binary = "php"
    }

    if ($file -eq "")
    {
        if (Test-Path "Dyno.phar")
        {
            $file = "Dyno.phar"
        }
        elseif (Test-Path "src\dyno\Server.php")
        {
            $file = "src\dyno\Boot.php"
        }
        else
        {
            echo "Couldn't find a valid Dyno installation"
            pause
            exit 1
        }
    }

    function StartServer
    {
        $command = "powershell -NoProfile " + $binary + " " + $file + " " + $extraDynoArgs
        iex $command
    }

    $loops = 0

    StartServer

    while ($Loop)
    {
        if ($loops -ne 0)
        {
            echo ("Restarted " + $loops + " times")
        }
        $loops++
        echo "To escape the loop, press CTRL+C now. Otherwise, wait 5 seconds for the server to restart."
        echo ""
        Start-Sleep 5
        StartServer
    }