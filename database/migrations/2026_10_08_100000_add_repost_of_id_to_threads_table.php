<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->text('body')->nullable()->change();
            $table->foreignId('repost_of_id')->nullable()->after('user_id')
                ->constrained('threads')->nullOnDelete();
            $table->index(['user_id', 'repost_of_id']);
        });
    }

    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'repost_of_id']);
            $table->dropConstrainedForeignId('repost_of_id');
            $table->text('body')->nullable(false)->change();
        });
    }
};
