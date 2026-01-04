<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_photo_path')->nullable()->after('note');
            $table->string('check_out_photo_path')->nullable()->after('check_in_photo_path');
            $table->decimal('check_in_lat', 10, 7)->nullable()->after('check_out_photo_path');
            $table->decimal('check_in_lng', 10, 7)->nullable()->after('check_in_lat');
            $table->integer('check_in_accuracy')->nullable()->after('check_in_lng');
            $table->decimal('check_out_lat', 10, 7)->nullable()->after('check_in_accuracy');
            $table->decimal('check_out_lng', 10, 7)->nullable()->after('check_out_lat');
            $table->integer('check_out_accuracy')->nullable()->after('check_out_lng');
            $table->string('location_status', 20)->nullable()->after('check_out_accuracy');
            $table->integer('distance_m')->nullable()->after('location_status');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_photo_path',
                'check_out_photo_path',
                'check_in_lat',
                'check_in_lng',
                'check_in_accuracy',
                'check_out_lat',
                'check_out_lng',
                'check_out_accuracy',
                'location_status',
                'distance_m',
            ]);
        });
    }
};
