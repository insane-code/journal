<?php

namespace Insane\Journal\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:install install
    {--ddd : Indicates if installation follows atmosphere Domain Driven Development}
    {--accounting : Indicates if accounting modules be installed}
    {--invoicing : Indicates if invoicing modules should be installed}
    {--products : Indicates if products modules should be installed}
    {--taxes : Indicates if taxes should be installed}
    {--payments : Indicates if payments should be installed}
    {--orders : Indicates if orders modules should be installed}
    {--inventory : Indicates if inventory modules should be installed}
    {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install insane journal components and resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->installAccounting();
    }


    /**
     * Install the Inertia stack into the application.
     *
     * @return void
     */
    protected function installAccounting()
    {

        // Publish...
        $this->callSilent('vendor:publish', ['--tag' => 'journal:config', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'journal-migrations', '--force' => true]); 
 
        // Storage...
        $this->callSilent('storage:link');

        $isDD = true;
        if (!$isDD) {
            // Models...
           copy(__DIR__.'/../../stubs/app/Models/Account.php', app_path('Models/Account.php'));
           copy(__DIR__.'/../../stubs/app/Models/Category.php', app_path('Models/Category.php'));
           copy(__DIR__.'/../../stubs/app/Models/Image.php', app_path('Models/Image.php'));
           copy(__DIR__.'/../../stubs/app/Models/Invoice.php', app_path('Models/Invoice.php'));
           copy(__DIR__.'/../../stubs/app/Models/InvoiceLine.php', app_path('Models/InvoiceLine.php'));
           copy(__DIR__.'/../../stubs/app/Models/Payment.php', app_path('Models/Payment.php'));
           copy(__DIR__.'/../../stubs/app/Models/Product.php', app_path('Models/Product.php'));
           copy(__DIR__.'/../../stubs/app/Models/ProductsOption.php', app_path('Models/ProductsOption.php'));
           copy(__DIR__.'/../../stubs/app/Models/ProductsVariant.php', app_path('Models/ProductsVariant.php'));
           copy(__DIR__.'/../../stubs/app/Models/Stock.php', app_path('Models/Stock.php'));
           copy(__DIR__.'/../../stubs/app/Models/Transaction.php', app_path('Models/Transaction.php'));
           copy(__DIR__.'/../../stubs/app/Models/TransactionLine.php', app_path('Models/TransactionLine.php'));
        } else {
            (new Filesystem)->ensureDirectoryExists(app_path('Domains/Journal'));
            // domain
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/app/Domains/Journal', app_path('Domains/Journal'));
            copy(__DIR__.'/../../stubs/app/Providers/JournalServiceProvider.php', app_path('Providers/JournalServiceProvider.php'));
        }

        $this->info('Inertia scaffolding installed successfully.');
        $this->comment('Please execute the "npm install && npm run dev" command to build your assets.');
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  mixed  $packages
     * @return void
     */
    protected function requireComposerPackages($packages)
    {
        $command = array_merge(
            ['composer', 'require'],
            is_array($packages) ? $packages : func_get_args()
        );

        (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            });
    }

    /**
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Delete the "node_modules" directory and remove the associated lock files.
     *
     * @return void
     */
    protected static function flushNodeModules()
    {
        tap(new Filesystem, function ($files) {
            $files->deleteDirectory(base_path('node_modules'));

            $files->delete(base_path('yarn.lock'));
            $files->delete(base_path('package-lock.json'));
        });
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $path
     * @return void
     */
    protected function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }
}
