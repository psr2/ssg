<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id(); // creates an auto-incrementing "id" primary key
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->text('address')->nullable();
            $table->string('abbreviation')->nullable();
            $table->text('status')->nullable();


            $table->timestamps(); // creates "created_at" and "updated_at"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
///.