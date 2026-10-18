<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_validator')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(true);
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->timestamps();
        });
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('document_number')->nullable();
            $table->date('document_date');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('keywords')->nullable();
            $table->string('physical_location')->nullable();
            $table->text('revision_note')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->uuid('submission_token')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'number']);
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('active_version_id')->references('id')->on('document_versions')->restrictOnDelete();
        });
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('decision');
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->string('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('document_files');
        Schema::table('documents', fn (Blueprint $table) => $table->dropForeign(['active_version_id']));
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropColumn(['is_admin', 'is_validator', 'is_active', 'must_change_password']);
        });
        Schema::dropIfExists('categories');
        Schema::dropIfExists('divisions');
    }
};
