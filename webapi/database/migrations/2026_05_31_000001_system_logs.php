<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->index();
            $table->string('channel', 40)->default('app')->index();
            $table->string('message', 500);
            $table->string('exception_class', 255)->nullable();
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('trace')->nullable();
            $table->json('context')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_path', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
