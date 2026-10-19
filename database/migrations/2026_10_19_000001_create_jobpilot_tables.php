<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ported from the JobPilotAI SQLAlchemy models. Every user-generated record
     * is owned by a user; discovered vacancies (job_listings) are shared globally.
     */
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('location')->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('github_url', 500)->nullable();
            $table->string('portfolio_url', 500)->nullable();
            $table->text('professional_summary')->nullable();
            $table->json('skills')->nullable();
            $table->json('education')->nullable();
            $table->json('experience')->nullable();
            $table->json('projects')->nullable();
            $table->json('certifications')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->json('preferred_countries')->nullable();
            $table->json('preferred_employment_types')->nullable();
            $table->json('preferred_keywords')->nullable();
            $table->unsignedBigInteger('min_salary')->nullable();
            $table->unsignedBigInteger('max_salary')->nullable();
            $table->timestamps();
        });

        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100)->index();
            $table->string('source_id');
            $table->string('title', 500);
            $table->string('company', 500)->nullable();
            $table->string('location')->nullable();
            $table->string('country', 100)->nullable()->index();
            $table->string('employment_type', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->string('salary_currency', 10)->nullable();
            $table->string('application_url', 2000)->nullable();
            $table->string('application_email')->nullable();
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['source', 'source_id']);
        });

        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->float('score')->index();
            $table->float('skill_score')->nullable();
            $table->float('experience_score')->nullable();
            $table->float('education_score')->nullable();
            $table->float('location_score')->nullable();
            $table->float('keyword_score')->nullable();
            $table->float('employment_type_score')->nullable();
            $table->text('explanation')->nullable();
            $table->json('missing_requirements')->nullable();
            $table->json('matched_skills')->nullable();
            $table->text('llm_analysis')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'job_listing_id']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 50)->default('pending')->index();
            $table->string('stage', 100)->nullable();
            $table->json('search_params')->nullable();
            $table->unsignedInteger('jobs_discovered')->default(0);
            $table->unsignedInteger('jobs_new')->default(0);
            $table->unsignedInteger('jobs_matched')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('application_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('recipient_email')->nullable();
            $table->boolean('recipient_verified')->default(false);
            $table->string('subject', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('cv_file_path', 1000)->nullable();
            $table->string('cv_file_hash', 64)->nullable();
            $table->string('content_hash', 64)->nullable()->index();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
        });

        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('approved_content_hash', 64);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_draft_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->string('sent_content_hash', 64)->nullable();
            $table->string('status', 50)->default('approved')->index();
            $table->string('provider_message_id', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
        Schema::dropIfExists('approval_records');
        Schema::dropIfExists('application_drafts');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('candidate_profiles');
    }
};
