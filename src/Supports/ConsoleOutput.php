<?php

namespace IbnulHusainan\Arc\Supports;

trait ConsoleOutput
{
    protected $notif;
    protected bool $isLaravelCommand = false;
    protected bool $runningInConsole = false;

    public function __construct()
    {
        if (is_callable([get_parent_class($this), '__construct'])) {
            parent::__construct();
        }

        $this->runningInConsole = app()->runningInConsole();
        $this->isLaravelCommand = is_subclass_of($this, \Illuminate\Console\Command::class);
        $this->notif = $this->isLaravelCommand ? $this : app('Symfony\Component\Console\Output\ConsoleOutput');
    }

    public function lineNew(string $message): void
    {
        $this->output($message, null);
    }

    public function lineOk(string $message): void
    {
        $this->output($message, 'cmdOk');
    }

    public function lineInfo(string $message): void
    {
        $this->output($message, 'cmdInfo');
    }

    public function lineError(string $message): void
    {
        $this->output($message, 'cmdError');
    }

    public function lineSpace(): void
    {
        $this->output('', null);
    }

    private function output(string $message, ?string $formatter): void
    {
        $message = $this->format($message);

        $formatted = $formatter ? $formatter($message) : $message;

        $this->notif = $this->notif ?? app('Symfony\Component\Console\Output\ConsoleOutput');

        if($this->isLaravelCommand) {
            $this->notif->line($formatted);
            $this->notif->newLine();
        } else {
            $this->notif->writeln($formatted);
            $this->notif->writeln('');
        }
    }

    private function format(string $message): string
    {
        return preg_replace('/\*(.*?)\*/', "\033[1m$1\033[0m", $message);
    }
}
