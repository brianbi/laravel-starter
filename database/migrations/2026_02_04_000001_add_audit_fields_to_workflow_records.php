<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_records', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->after('comment');
            $table->string('user_agent', 500)->nullable()->after('ip');
            $table->string('request_id', 50)->nullable()->after('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_records', function (Blueprint $table) {
            $table->dropColumn(['ip', 'user_agent', 'request_id']);
        });
    }
};
