<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    use HasFactory;

    protected $table = 'presences';

    // Menambahkan kolom yang bisa diisi secara mass-assignment
    protected $fillable = [
        'user_id',
        'date',
        'time',
        'status'
    ];

    // Definisikan hubungan dengan model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}