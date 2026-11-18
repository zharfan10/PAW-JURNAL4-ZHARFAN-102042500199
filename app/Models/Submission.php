<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tugas_id',
        'user_id',
        'file_path',
        'nilai',
        'catatan_dosen',
    ];

    // Relasi ke Tugas
    public function tugas()
    {
        return $this->belongsTo(Tugas::class);
    }

    // Relasi ke User (Mahasiswa)
    public function mahasiswa()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // KOREKSI: Tambahkan 'return' pada pengecekan is_null()
    protected function statusKelulusan(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (is_null($this->nilai)) {
                    return 'Belum Dinilai'; // <-- Sebelumnya kurang kata 'return'
                }
                return $this->nilai >= 70 ? 'Lulus' : 'Remedial';
            }
        );
    }
}