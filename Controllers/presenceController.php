<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presence;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\Auth;
use DB;

class PresenceController extends Controller
{
    /**
     * Fungsi untuk mencatat presensi user.
     */
    public function store(Request $request)
{
    // Ambil user yang sedang login
    $user = Auth::user();

    // Validasi input request
    $validated = $request->validate([
        'status' => 'required|in:hadir,izin,sakit,alpha',
        'date' => 'nullable|date|after_or_equal:today' // Validasi untuk memastikan tanggal yang valid
    ]);

    // Tanggal presensi: jika `date` tidak diisi, gunakan tanggal hari ini
    $date = $validated['date'] ?? now()->toDateString();

    // Cek apakah user yang login adalah admin
    if ($user->role === 'admin') {
        // Validasi input untuk admin (memerlukan user_id)
        $adminValidated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $adminValidated['user_id'];
    } else {
        // Cek apakah siswa mencoba melakukan presensi untuk siswa lain
        if ($request->has('user_id') && $request->input('user_id') != $user->id) {
            return response()->json([
                'status' => 'gagal',
                'message' => 'Anda tidak bisa melakukan presensi untuk siswa lain.'
            ], 403);
        }

        $userId = $user->id;
    }

    // Cek apakah sudah ada presensi pada tanggal yang sama
    $existingPresence = Presence::where('user_id', $userId)
        ->where('date', $date)
        ->first();

    if ($existingPresence) {
        return response()->json([
            'status' => 'gagal',
            'message' => 'Anda sudah melakukan presensi pada tanggal ini. Silakan lakukan presensi besok.'
        ], 400);
    }

    // Menyimpan presensi baru
    $presence = Presence::create([
        'user_id' => $userId,
        'date' => $date,
        'time' => now()->toTimeString(),  // Menggunakan waktu saat ini untuk waktu
        'status' => $validated['status'],
    ]);

    // Mengembalikan response JSON
    return response()->json([
        'status' => 'sukses',
        'message' => 'Presensi berhasil dicatat',
        'data' => [
            'id' => $presence->id,
            'user_id' => $presence->user_id,
            'date' => $presence->date,
            'time' => $presence->time,
            'status' => $presence->status,
        ]
    ]);
}
public function riwayat(Request $request, $user_id = null)
{
    $user = auth()->user();

    // Admin dapat melihat riwayat presensi siapa saja
    if ($user->role === 'admin') {
        // Jika admin tidak menyertakan user_id, tampilkan semua presensi
        $presences = $user_id 
            ? Presence::where('user_id', $user_id)->get()
            : Presence::all();
    } 
    // Siswa hanya bisa melihat riwayatnya sendiri
    else if ($user->role === 'siswa') {
        // Jika siswa mencoba mengakses presensi siswa lain
        if ($user_id && $user_id != $user->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk melihat riwayat presensi siswa lain.'
            ], 403);
        }

        // Menampilkan riwayat presensi siswa yang sedang login
        $presences = Presence::where('user_id', $user->id)->get();
    } else {
        return response()->json([
            'message' => 'Role tidak dikenali.'
        ], 403);
    }

    // Jika tidak ada presensi yang ditemukan
    if ($presences->isEmpty()) {
        return response()->json([
            'message' => 'Riwayat presensi tidak ditemukan.'
        ], 404);
    }

    return response()->json([
        'message' => 'Riwayat presensi ditemukan.',
        'data' => $presences
    ]);
}
public function riwayatByUserId($user_id)
{
    // Cek apakah user yang login adalah admin
    $user = Auth::user();

    if ($user->role !== 'admin') {
        return response()->json([
            'message' => 'Anda tidak memiliki izin untuk mengakses riwayat presensi ini.'
        ], 403); // Forbidden
    }

    // Ambil riwayat presensi berdasarkan user_id
    $presences = Presence::where('user_id', $user_id)->get();

    if ($presences->isEmpty()) {
        return response()->json([
            'message' => 'Riwayat presensi tidak ditemukan untuk user ini.'
        ], 404); // Not Found
    }

    return response()->json([
        'message' => 'Riwayat presensi ditemukan.',
        'data' => $presences
    ]);
}

public function monthlySummary($user_id)
{
    try {
        // Mendapatkan bulan dan tahun saat ini
        $date = new DateTime();
        $month = $date->format('m');
        $year = $date->format('Y');

        // Mengambil rekap kehadiran bulanan berdasarkan user_id, tahun, dan bulan
        $summary = Presence::where('user_id', $user_id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->select(DB::raw('sum(case when status = "Hadir" then 1 else 0 end) as hadir'),
                     DB::raw('sum(case when status = "Izin" then 1 else 0 end) as izin'),
                     DB::raw('sum(case when status = "Sakit" then 1 else 0 end) as sakit'),
                     DB::raw('sum(case when status = "Alpha" then 1 else 0 end) as alpa'))
            ->first();

        // Format respons
        $data = [
            'user_id' => $user_id,
            'month' => $month . '-' . $year,  // Format bulan-tahun
            'attendance_summary' => [
                'hadir' => $summary->hadir,
                'izin' => $summary->izin,
                'sakit' => $summary->sakit,
                'alpa' => $summary->alpa,
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function analyzePresence(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'group_by' => 'required|string|in:kelas,jabatan', // Bisa dikelompokkan berdasarkan kelas atau jabatan
    ]);

    // Ambil data kehadiran berdasarkan periode yang ditentukan
    $PresenceData = Presence::whereBetween('date', [$request->start_date, $request->end_date])
        ->with('user') // Pastikan Anda memiliki relasi user di model Attendance
        ->get();

    // Analisis data kehadiran untuk satu grup berdasarkan group_by
    $groupedAnalysis = [];
    $totalHadir = 0;
    $totalIzin = 0;
    $totalSakit = 0;
    $totalAlpha = 0;
    $totalUsers = 0;

    foreach ($PresenceData as $Presence) {
        $groupKey = $Presence->user->{$request->group_by}; // Ambil kelas atau jabatan sesuai group_by

        if (!isset($groupedAnalysis[$groupKey])) {
            $groupedAnalysis[$groupKey] = [
                'total_users' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'total_days' => 0,
            ];
        }

        // Hitung kehadiran berdasarkan status
        $groupedAnalysis[$groupKey]['total_users']++;
        $groupedAnalysis[$groupKey]['total_days']++;

        switch ($Presence->status) {
            case 'hadir':
                $groupedAnalysis[$groupKey]['hadir']++;
                $totalHadir++;
                break;
            case 'izin':
                $groupedAnalysis[$groupKey]['izin']++;
                $totalIzin++;
                break;
            case 'sakit':
                $groupedAnalysis[$groupKey]['sakit']++;
                $totalSakit++;
                break;
            case 'alpha':
                $groupedAnalysis[$groupKey]['alpha']++;
                $totalAlpha++;
                break;
        }
    }

    // Menghitung persentase kehadiran untuk grup yang dipilih
    $groupKey = key($groupedAnalysis); // Ambil grup pertama karena hanya ada satu grup yang dipilih
    $data = $groupedAnalysis[$groupKey];

    $PresenceRate = ($data['hadir'] / $data['total_days']) * 100;
    $hadirPercentage = ($data['hadir'] / $data['total_days']) * 100;
    $izinPercentage = ($data['izin'] / $data['total_days']) * 100;
    $sakitPercentage = ($data['sakit'] / $data['total_days']) * 100;
    $alphaPercentage = ($data['alpha'] / $data['total_days']) * 100;

    // Format respons
    $response = [
        'status' => 'success',
        'data' => [
            'analysis_period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'grouped_analysis' => [
                [
                    'group' => $groupKey,
                    'total_users' => $data['total_users'],
                    'Presence_rate' => $PresenceRate,
                    'hadir_percentage' => $hadirPercentage,
                    'izin_percentage' => $izinPercentage,
                    'sakit_percentage' => $sakitPercentage,
                    'alpha_percentage' => $alphaPercentage,
                    'total_Presence' => [
                        'hadir' => $data['hadir'],
                        'izin' => $data['izin'],
                        'sakit' => $data['sakit'],
                        'alpha' => $data['alpha'],
                    ]
                ]
            ],
            'total_Presence' => [
                'hadir' => $totalHadir,
                'izin' => $totalIzin,
                'sakit' => $totalSakit,
                'alpha' => $totalAlpha,
            ]
        ]
    ];

    return response()->json($response);
}

}
