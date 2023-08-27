<?php

namespace Insane\Journal;

use Illuminate\Support\ServiceProvider;
use Insane\Journal\Console\SetAccountsCommand;
use Insane\Journal\Console\SetChartAccountsCommand;

class JournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/journal.php', 'journal');
        $this->publishConfig();
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SetAccountsCommand::class,
                SetChartAccountsCommand::class
            ]);
        }
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
    }

    /**
    * Get route group configuration array.
    *
    * @return array
    */
    private function routeConfiguration()
    {
        return [
            'namespace'  => "Insane\Journal\Http\Controllers",
            'middleware' => 'api',
            'prefix'     => 'api'
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register facade
        $this->app->singleton('journal', function () {
            return new Journal;
        });
    }

    /**
     * Publish Config
     *
     * @return void
     */
    public function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/journal.php' => config_path('journal.php'),
            ], 'journal:config');

            $this->publishes([
            __DIR__ . '/database/seeders/accounting.php' => database_path('seeds/accounting.php'),
            ], 'journal:accounting-seeds');

            $this->publishes([
                __DIR__.'/../database/migrations/2021_04_17_100000_create_accounts_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_200000_create_categories_table.php' => database_path('migrations/2020_05_21_200000_create_team_user_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_300000_create_transactions_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_400000_create_transaction_lines_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_500000_create_payment_documents_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_600000_create_payments_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_700000_create_taxes_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_800000_create_account_detail_types_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
                __DIR__.'/../database/migrations/2021_04_17_900000_create_payees_table.php' => database_path('migrations/2020_05_21_300000_create_team_invitations_table.php'),
            ], 'journal-core-migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/2021_04_19_100000_create_products_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_19_200000_create_images_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_19_300000_create_product_options_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_19_400000_create_product_option_values_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_19_500000_create_product_prices_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_19_600000_create_product_variants_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_19_700000_create_product_taxes_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
            ], 'journal-products-migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/2021_04_20_100000_create_invoices_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_20_200000_create_invoice_lines_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_20_300000_create_invoice_line_taxes_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_20_400000_create_invoice_delivery_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_20_500000_create_invoice_logs_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
                __DIR__.'/../database/migrations/2021_04_20_600000_create_document_types_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
            ], 'journal-invoicing-migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/2021_04_21_100000_create_stocks_table.php' => database_path('migrations/2020_05_21_100000_create_teams_table.php'),
            ], 'journal-inventory-migrations');
        }
    }
}
