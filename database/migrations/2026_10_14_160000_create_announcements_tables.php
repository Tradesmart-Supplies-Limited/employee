<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title', 150);
            $t->text('body');
            $t->string('category', 30)->default('general');   // general | policy | event | holiday | training | safety | welfare
            $t->string('priority', 20)->default('normal');    // normal | important | urgent
            $t->json('departments')->nullable();               // null / [] = everyone
            $t->boolean('pinned')->default(false);
            $t->timestamp('publish_at')->nullable();           // null = straight away
            $t->timestamp('expires_at')->nullable();
            $t->boolean('send_email')->default(false);
            $t->timestamp('emailed_at')->nullable();
            $t->unsignedInteger('emailed_count')->default(0);
            $t->string('attachment_path')->nullable();
            $t->string('attachment_name')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['publish_at', 'expires_at']);
        });

        Schema::create('announcement_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->timestamp('read_at');
            $t->unique(['announcement_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
