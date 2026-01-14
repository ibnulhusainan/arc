<?php

if(!function_exists('cmdError')) {
    function cmdError(string $msg)
    {
        return "  \033[41;97m ERROR \033[0m $msg";
    }
}

if(!function_exists('cmdOk')) {
    function cmdOk(string $msg)
    {
        return "  \033[42;97m OK \033[0m $msg";
    }
}

if(!function_exists('cmdWarn')) {
    function cmdWarn(string $msg)
    {
        return "  \033[42;97m WARNING \033[0m $msg";
    }
}

if(!function_exists('cmdInfo')) {
    function cmdInfo(string $msg)
    {
        return "  \033[44;97m INFO \033[0m $msg";
    }
}


if(!function_exists('cmdBold')) {
    function cmdBold(string $msg)
    {
        return "\033[1m{$msg}\033[0m";
    }
}

if(!function_exists('cmdNotif')) {
    function cmdNotif()
    {
        return app('Symfony\Component\Console\Output\ConsoleOutput');
    }
}

if(!function_exists('cmdCreated')) {
    function cmdCreated(string $module): void
    {
        $host = env('APP_HOST', '127.0.0.1');
        $port = env('APP_PORT', 8000);
        $appUrl = config('app.url') ?? "http://{$host}:{$port}";
        $moduleSlug = strtolower($module);
        $_module = array_map('ucfirst', explode("/", $moduleSlug));
        $moduleName = end($_module);

        $notif = cmdNotif();

        $notif->writeln('');
        $notif->writeln(
            cmdOk(cmdBold("[{$moduleName}]") . " module successfully created!")
        );
        $notif->writeln('');
        $notif->writeln("🚀 All set! Explore your new module here → {$appUrl}/{$moduleSlug}");
        $notif->writeln('');
    }
}

if(!function_exists('cmdRemoved')) {
    function cmdRemoved(string $module): void
    {
        $notif = cmdNotif();

        $moduleSlug = strtolower($module);
        $_module = array_map('ucfirst', explode("/", $moduleSlug));
        $moduleName = end($_module);

        $notif->writeln('');
        $notif->writeln('');
        $notif->writeln(
            cmdOk(cmdBold("[{$moduleName}]") . " module successfully removed!")
        );
        $notif->writeln('');
    }
}