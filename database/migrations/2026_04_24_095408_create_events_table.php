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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 100);
            $table->text('lineup');
            $table->text('description');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 8, 2)->default(0);
            $table->string('price_info', 100)->nullable();
            $table->string('ticket_link', 100)->nullable();
            $table->string('location_name', 100);
            $table->string('neighborhood', 100);
            $table->string('flyer', 100)->nullable(); 
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_18_plus')->default(true);
            $table->timestamps();
            $table->string('stamp_token')->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
