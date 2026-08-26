<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cadre_review_proposals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 200);
            $table->integer('proposal_year');   // the year the new cadre takes effect
            $table->string('status', 30)->default('draft'); // draft|submitted|director_approved|moh_submitted|approved|rejected
            $table->text('justification')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->string('moh_reference', 80)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['proposal_year','status'], 'idx_crp_year_status');
        });

        Schema::create('cadre_review_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('proposal_id');
            $table->unsignedBigInteger('position_id');
            $table->integer('current_approved');
            $table->integer('proposed_amount');  // net change applied to current
            $table->string('change_type', 20)->default('increase'); // increase|decrease|no_change
            $table->text('justification')->nullable();
            $table->timestamps();

            $table->foreign('proposal_id')->references('id')->on('cadre_review_proposals')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions');
            $table->unique(['proposal_id','position_id'], 'uniq_cri_proposal_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadre_review_items');
        Schema::dropIfExists('cadre_review_proposals');
    }
};
