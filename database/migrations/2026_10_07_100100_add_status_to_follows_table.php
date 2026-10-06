<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            // accepted | pending
            $table->string('status', 10)->default('accepted')->after('following_id');
            $table->index(['following_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex(['following_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
