<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presenca_id')->constrained('presencas')->onDelete('cascade');
            $table->string('codigo')->unique();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('certificados');
    }
};
