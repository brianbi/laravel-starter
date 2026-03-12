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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('模板编码');
            $table->string('name', 100)->comment('模板名称');
            $table->string('channel', 20)->comment('适用渠道');
            $table->string('title_template', 200)->comment('标题模板');
            $table->text('content_template')->comment('内容模板');
            $table->json('variables')->nullable()->comment('可用变量说明');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1启用');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index('channel');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
