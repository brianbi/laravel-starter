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
        Schema::create('notification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('接收用户ID');
            $table->string('channel', 20)->comment('渠道: database/mail/dingtalk/wechat/sms');
            $table->string('type', 100)->comment('通知类型类名');
            $table->string('title', 200)->comment('通知标题');
            $table->text('content')->nullable()->comment('通知内容');
            $table->json('data')->nullable()->comment('附加数据');
            $table->tinyInteger('status')->default(0)->comment('状态: 0待发送 1已发送 2发送失败');
            $table->string('error_message')->nullable()->comment('失败原因');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('channel');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_records');
    }
};
