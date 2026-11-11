<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave requests:
 *  - a comment thread everyone in the approval chain (and the applicant) can add to,
 *    for example after a request has been reverted;
 *  - a higher approver can override a revert made at an earlier step.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_comments')) {
            Schema::create('leave_comments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('author_name');
                $t->string('author_role', 40)->nullable();   // Applicant / Supervisor / HR / Management
                $t->string('kind', 20)->default('comment');   // comment | override
                $t->text('body');
                $t->timestamps();
                $t->index(['leave_request_id', 'created_at']);
            });
        }

        Schema::table('leave_requests', function (Blueprint $t) {
            if (! Schema::hasColumn('leave_requests', 'overridden_step')) {
                $t->string('overridden_step', 20)->nullable();
                $t->unsignedBigInteger('overridden_by')->nullable();
                $t->string('overridden_by_name')->nullable();
                $t->timestamp('overridden_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_comments');
        Schema::table('leave_requests', function (Blueprint $t) {
            $t->dropColumn(['overridden_step', 'overridden_by', 'overridden_by_name', 'overridden_at']);
        });
    }
};
