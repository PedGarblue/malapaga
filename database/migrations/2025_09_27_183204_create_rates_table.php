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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['BCV', 'Paralelo', 'Custom']);
            $table->decimal('value', 12, 4);
            $table->string('currency_from', 3)->default('VES');
            $table->string('currency_to', 3);
            $table->timestamp('effective_at'); // was fetched_at
            $table->timestamps(); // created_at + updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
