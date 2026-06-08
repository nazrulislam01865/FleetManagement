<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        $paymentTypes = [
            ['code' => 'CASH', 'name' => 'Cash'],
            ['code' => 'BANK_TRANSFER', 'name' => 'Bank Transfer'],
            ['code' => 'CARD', 'name' => 'Card'],
            ['code' => 'BKASH', 'name' => 'bKash'],
            ['code' => 'NAGAD', 'name' => 'Nagad'],
            ['code' => 'ROCKET', 'name' => 'Rocket'],
            ['code' => 'CHEQUE', 'name' => 'Cheque'],
            ['code' => 'OTHER', 'name' => 'Other'],
        ];

        foreach ($paymentTypes as $index => $paymentType) {
            DB::table('fleet_payment_types')->insert([
                'code' => $paymentType['code'],
                'name' => $paymentType['name'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_payment_types');
    }
};
