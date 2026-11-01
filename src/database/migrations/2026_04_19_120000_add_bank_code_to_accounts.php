<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('accounts', 'bank_code')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('bank_code')->nullable()->after('alias');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('bank_code');
        });
    }
};
