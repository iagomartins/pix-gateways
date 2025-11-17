<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pix_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('gateway_id')->constrained('gateways')->onDelete('cascade');
            $table->string('external_id')->nullable()->index();
            $table->enum('status', ['PENDING', 'PROCESSING', 'CONFIRMED', 'PAID', 'CANCELLED', 'FAILED'])->default('PENDING');
            $table->decimal('amount', 10, 2);
            $table->string('payer_name')->nullable();
            $table->string('payer_cpf', 11)->nullable();
            $table->text('qr_code')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['gateway_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pix_transactions');
    }
};

