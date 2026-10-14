<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');           // earlier than start_time = crosses midnight
            $table->decimal('hours', 5, 2);

            // weekday | saturday | sunday | public_holiday
            $table->string('day_type')->default('weekday');

            $table->text('reason');

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
