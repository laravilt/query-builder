<?php

namespace Laravilt\QueryBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class InstallQueryBuilderCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'query-builder:install
                            {--force : Overwrite existing files}
                            {--without-assets : Skip asset publishing}';

    /**
     * The console command description.
     */
    protected $description = 'Install QueryBuilder plugin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing QueryBuilder plugin...');
        $this->newLine();

        // Publish config
        $this->publishConfig();

        $this->publishAssets();
        if (! $this->option('without-assets')) {
            $this->buildAssets();
        }
        $this->newLine();
        $this->info('✅ QueryBuilder plugin installed successfully!');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfig(): void
    {
        $this->info('Publishing configuration...');

        $params = ['--tag' => 'laravilt-query-builder-config'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', $params, $this->output);
    }

    protected function publishAssets(): void
    {
        $this->info('Publishing assets...');

        $this->call('vendor:publish', [
            '--tag' => 'query-builder-assets',
            '--force' => true,
        ]);

        $this->components->success('Assets published successfully!');
    }

    protected function buildAssets(): void
    {
        $this->info('Building assets...');

        $process = Process::path(base_path('packages/laravilt/query-builder'))
            ->run('npm install && npm run build');

        if ($process->successful()) {
            $this->components->success('Assets built successfully!');
        } else {
            $this->components->error('Failed to build assets: '.$process->errorOutput());
        }
    }
}
