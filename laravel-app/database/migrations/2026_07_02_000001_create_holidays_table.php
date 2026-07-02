<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Minimal, manually-managed national holiday calendar (STIKES Advaita leave
// policy point 3 & 4). Internal campus holidays are NOT regulated yet, so
// this table intentionally holds only official/national holidays entered by
// an admin — there is no external API fetch and no UI CRUD in this phase.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
