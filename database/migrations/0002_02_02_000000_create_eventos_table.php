<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->dateTime('data_inicio');
            $table->dateTime('data_final');
            $table->tinyInteger('cancelado')->default(0);
            $table->timestamps(); // created_at e updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('eventos');
    }
};
