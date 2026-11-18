<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->isMahasiswa()) {
            abort(403, 'Akses ditolak. Pengumpulan tugas hanya dapat dilakukan oleh Mahasiswa.');
        }

        $user = Auth::user();

        $enrollment = $assignment->course->students()->where('users.id', $user->id)->first();
        if (!$enrollment || $enrollment->pivot->status !== 'approved') {
            abort(403, 'Akses ditolak. Anda belum disetujui oleh Dosen untuk mengikuti mata kuliah tugas ini.');
        }

        if ($assignment->isOverdue()) {
            return back()->with('error', 'Gagal mengumpulkan: Batas waktu pengumpulan (deadline) tugas telah berakhir.');
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'string', 'min:10', 'max:20000'],
        ], [
            'jawaban.required' => 'Teks jawaban tugas wajib diisi.',
            'jawaban.min' => 'Jawaban terlalu pendek. Tuliskan jawaban minimal 10 karakter.',
            'jawaban.max' => 'Jawaban melebihi batas maksimal 20.000 karakter.',
        ]);

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'jawaban' => $validated['jawaban'],
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Jawaban tugas Anda berhasil disimpan ke sistem.');
    }

    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 1. Pastikan dosen yang login adalah pengampu mata kuliah dari tugas ini
        if ($submission->assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // 2. Validasi input nilai dan catatan dosen
        $validated = $request->validate([
            'nilai' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_dosen' => ['nullable', 'string', 'max:1000'],
        ], [
            'nilai.required' => 'Nilai wajib diisi.',
            'nilai.integer' => 'Nilai harus berupa angka.',
            'nilai.min' => 'Nilai minimal adalah 0.',
            'nilai.max' => 'Nilai maksimal adalah 100.',
            'catatan_dosen.max' => 'Catatan dosen maksimal 1000 karakter.',
        ]);

        // 3. Update data submission dengan nilai dan catatan dosen
        $submission->update([
            'nilai' => $validated['nilai'],
            'catatan_dosen' => $validated['catatan_dosen'] ?? null,
        ]);

        return back()->with('success', 'Nilai tugas mahasiswa berhasil disimpan.');
    }

    public function exportCsv(Assignment $assignment): StreamedResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 1. Pastikan dosen yang login adalah pengampu mata kuliah tugas ini
        if ($assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // 2. Muat relasi mahasiswa yang disetujui (approvedStudents) dan data submissions milik assignment ini
        $assignment->load(['course.approvedStudents', 'submissions']);

        $fileName = 'rekap_nilai_' . str_slug($assignment->judul) . '_' . date('Y-m-d') . '.csv';

        // 3. Kembalikan response stream download untuk file CSV
        return response()->streamDownload(function () use ($assignment) {
            $file = fopen('php://output', 'w');

            // Tulis Header Kolom CSV
            fputcsv($file, ['No', 'Nomor Induk', 'Nama Mahasiswa', 'Status Kumpul', 'Waktu Kumpul', 'Nilai', 'Status Kelulusan', 'Catatan Dosen']);

            $students = $assignment->course->approvedStudents;
            foreach ($students as $index => $student) {
                $submission = $assignment->submissions->where('user_id', $student->id)->first();

                $statusKumpul = $submission ? 'Sudah Mengumpulkan' : 'Belum Mengumpulkan';
                $waktuKumpul = $submission && $submission->submitted_at ? $submission->submitted_at : '-';
                $nilai = $submission && !is_null($submission->nilai) ? $submission->nilai : 'Belum Dinilai';
                
                // Menentukan status kelulusan (Lulus jika nilai >= 70, Remedial jika ada nilai < 70, Belum Dinilai jika kosong)
                if (!$submission || is_null($submission->nilai)) {
                    $statusKelulusan = 'Belum Dinilai';
                } else {
                    $statusKelulusan = $submission->nilai >= 70 ? 'Lulus' : 'Remedial';
                }

                $catatan = $submission && $submission->catatan_dosen ? $submission->catatan_dosen : '-';

                fputcsv($file, [
                    $index + 1,
                    $student->nomor_induk ?? '-',
                    $student->name,
                    $statusKumpul,
                    $waktuKumpul,
                    $nilai,
                    $statusKelulusan,
                    $catatan,
                ]);
            }

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}