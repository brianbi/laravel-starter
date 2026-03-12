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
        // 字典表
        Schema::create('dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('字典名称');
            $table->string('code', 100)->unique()->comment('字典编码');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1正常');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->timestamps();

            $table->index('status');
            $table->index('code');
        });

        // 字典项表
        Schema::create('dictionary_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dictionary_id')->comment('字典ID');
            $table->string('label', 100)->comment('显示标签');
            $table->string('value', 100)->comment('值');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1正常');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->timestamps();

            $table->foreign('dictionary_id')->references('id')->on('dictionaries')->onDelete('cascade');
            $table->index('dictionary_id');
            $table->index('status');
            $table->index('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionary_items');
        Schema::dropIfExists('dictionaries');
    }
};
