<?php

namespace Nyamort\LaravelAnonymizer\Commands;

use Illuminate\Console\Command;

class LaravelAnonymizerCommand extends Command
{
    public $signature = 'laravel-anonymizer';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
