<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leave approval chain: Employee → Supervisor → HR → Director.
 *
 *  - leave_approval_settings: which steps are switched on (set by an Admin)
 *  - leave_requests: the chain each request follows (fixed when it is
 *    submitted), the step it is waiting on, and the Director's decision.
 *
 * Existing requests keep the old two-step chain (supervisor → HR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_approval_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('supervisor_step')->default(true);
            $t->boolean('hr_step')->default(true);
            $t->boolean('director_step')->default(true);
            $t->decimal('director_min_days', 5, 1)->default(0);   // 0 = every request goes to the Director
            $t->unsignedBigInteger('director_user_id')->nullable(); // null = any Director login
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::table('leave_requests', function (Blueprint $t) {
            $t->json('approval_chain')->nullable()->after('status');
            $t->string('current_step', 20)->nullable()->after('approval_chain')->index();

            $t->string('director_decision', 20)->nullable();
            $t->text('director_comments')->nullable();
            $t->string('director_name')->nullable();
            $t->string('director_position')->nullable();
            $t->date('director_signature_date')->nullable();
            $t->unsignedBigInteger('director_acted_by')->nullable();
            $t->timestamp('director_acted_at')->nullable();
        });

        // Requests already in progress continue on the old chain.
        DB::table('leave_requests')->where('status', 'Pending')->whereNull('supervisor_decision')->update(['current_step' => 'supervisor']);
        DB::table('leave_requests')->where('status', 'Pending')->whereNotNull('supervisor_decision')->update(['current_step' => 'hr']);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $t) {
            $t->dropIndex(['current_step']);
            $t->dropColumn(['approval_chain', 'current_step', 'director_decision', 'director_comments', 'director_name',
                'director_position', 'director_signature_date', 'director_acted_by', 'director_acted_at']);
        });
        Schema::dropIfExists('leave_approval_settings');
    }
};
