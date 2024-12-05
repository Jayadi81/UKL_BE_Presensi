<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Presence::create([
            'user_id' => $request->user_id,
            'date' => $request->date,
            'time' => $request->time,
            'status' => $request->status, // Pastikan kolom ini sesuai dengan tipe datanya
        ]);        
    }

    public function down() {
        Schema::table('presences', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

