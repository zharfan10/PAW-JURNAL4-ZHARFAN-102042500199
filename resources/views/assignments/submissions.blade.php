@extends('layouts.app')

@section('title', 'Daftar Pengumpulan — ' . $assignment->judul)

@section('content')
<div style="margin-bottom: 1.25rem;">
    <a href="{{ route('courses.show', $assignment->course_id) }}" class="btn btn-secondary btn-sm">
        Kembali ke Mata Kuliah
    </a>
</div>

@php
    $totalStudents = $students->count();
    $totalSubmitted = $submissions->count();
    $gradedSubmissions = $submissions->whereNotNull('nilai');
    $averageScore = $gradedSubmissions->count() > 0 ? round($gradedSubmissions->avg('nilai'), 1) : null;
@endphp

<!-- Header Penugasan -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="code-pill">{{ $assignment->course->kode_mk }}</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $assignment->course->nama_mk }}</span>
            </div>
            <h1 class="card-title">{{ $assignment->judul }}</h1>
            <p class="card-subtitle">
                Batas Pengumpulan: <strong>{{ $assignment->deadline ? $assignment->deadline->translatedFormat('l, d F Y — H:i') . ' WIB' : '-' }}</strong>
                @if($assignment->isOverdue())
                    <span style="color: #dc2626; font-size: 0.8125rem; font-weight: 600; margin-left: 0.5rem;">Batas Waktu Terlewat</span>
                @else
                    <span style="color: #059669; font-size: 0.8125rem; font-weight: 600; margin-left: 0.5rem;">Sedang Berlangsung</span>
                @endif
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            
            {{-- TODO 7b: Tambahkan tombol Export CSV --}}
            <a href="{{ route('assignments.export-csv', $assignment->id) }}" class="btn btn-success btn-sm">
                📥 Export ke CSV
            </a>

            <a href="{{ route('assignments.edit', $assignment->id) }}" class="btn btn-secondary btn-sm">
                Edit Tugas
            </a>
        </div>
    </div>

    <div class="card-body" style="background-color: #f8fafc; border-top: 1px solid var(--border-color); padding: 1rem 1.25rem;">
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">PETUNJUK PENUGASAN:</div>
        <div style="font-size: 0.925rem; color: var(--text-main); white-space: pre-wrap; line-height: 1.6;">{{ $assignment->deskripsi }}</div>
    </div>
</div>

<!-- Statistik Pengumpulan & Nilai (TODO 7a) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="margin-bottom: 0; padding: 1rem 1.25rem;">
        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TOTAL MAHASISWA</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 0.25rem;">{{ $totalStudents }}</div>
    </div>
    <div class="card" style="margin-bottom: 0; padding: 1rem 1.25rem;">
        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">SUDAH MENGUMPULKAN</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.25rem;">{{ $totalSubmitted }} <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-muted);">/ {{ $totalStudents }}</span></div>
    </div>
    <div class="card" style="margin-bottom: 0; padding: 1rem 1.25rem;">
        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">RATA-RATA NILAI KELAS</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #2563eb; margin-top: 0.25rem;">{{ $averageScore !== null ? $averageScore : 'Belum Ada' }}</div>
    </div>
</div>

<!-- Daftar Pengumpulan Mahasiswa -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 class="card-title" style="font-size: 1.1rem;">Rekapitulasi Pengumpulan & Penilaian Mahasiswa</h2>
            <p class="card-subtitle">Semua mahasiswa yang terdaftar resmi di kelas mata kuliah ini</p>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        @if($students->isEmpty())
            <div class="empty-state" style="padding: 3rem 1.5rem;">
                <p style="font-weight: 600; margin-bottom: 0.25rem;">Belum ada mahasiswa yang disetujui.</p>
                <p style="font-size: 0.875rem; color: var(--slate-500);">Mahasiswa perlu mengajukan pendaftaran dan disetujui untuk mengikuti kelas mata kuliah ini terlebih dahulu.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">No</th>
                            <th>Mahasiswa</th>
                            <th style="width: 130px;">NIM</th>
                            <th style="width: 160px;">Status & Waktu</th>
                            <th>Jawaban Teks</th>
                            <th style="min-width: 280px;">Penilaian Dosen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $sub = $submissions->get($student->id);
                            @endphp
                            <tr>
                                <td style="color: var(--text-muted); font-size: 0.875rem;">{{ $index + 1 }}</td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main);">{{ $student->name }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $student->email }}</div>
                                </td>
                                <td>
                                    <span class="code-pill">{{ $student->nomor_induk ?? '-' }}</span>
                                </td>
                                <td>
                                    @if($sub)
                                        <div style="color: #059669; font-weight: 600; font-size: 0.8125rem;">Sudah Mengumpulkan</div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.125rem;">
                                            {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y, H:i') : $sub->updated_at->translatedFormat('d M Y, H:i') }} WIB
                                        </div>
                                        <div style="margin-top: 0.35rem;">
                                            @if($sub->nilai !== null)
                                                <span style="display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; background-color: {{ $sub->nilai >= 70 ? '#d1fae5' : '#fef3c7' }}; color: {{ $sub->nilai >= 70 ? '#065f46' : '#92400e' }};">
                                                    {{ $sub->nilai }}/100 ({{ $sub->status_nilai }})
                                                </span>
                                            @else
                                                <span style="display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; background-color: #f1f5f9; color: #475569;">
                                                    {{ $sub->status_nilai }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color: #d97706; font-weight: 600; font-size: 0.8125rem;">Belum Mengumpulkan</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub)
                                        <details style="cursor: pointer;">
                                            <summary style="font-weight: 600; color: var(--tel-red); font-size: 0.875rem; outline: none;">
                                                Lihat Jawaban ({{ strlen($sub->jawaban) }} Karakter)
                                            </summary>
                                            <div style="margin-top: 0.75rem; padding: 0.875rem; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; max-height: 300px; overflow-y: auto;">{{ $sub->jawaban }}</div>
                                        </details>
                                    @else
                                        <span style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">Tidak ada kiriman</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub)
                                        {{-- TODO 7c: Form PATCH untuk input nilai dan catatan dosen --}}
                                        <form action="{{ route('submissions.grade', $sub->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            @csrf
                                            @method('PATCH')
                                            <div style="display: flex; gap: 0.5rem;">
                                                <input 
                                                    type="number" 
                                                    name="nilai" 
                                                    class="form-control" 
                                                    placeholder="Nilai (0-100)" 
                                                    value="{{ old('nilai', $sub->nilai) }}" 
                                                    min="0" 
                                                    max="100" 
                                                    required 
                                                    style="width: 110px; font-size: 0.85rem; padding: 0.35rem 0.5rem;"
                                                >
                                                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1; font-size: 0.85rem;">Simpan Nilai</button>
                                            </div>
                                            <textarea 
                                                name="catatan_dosen" 
                                                class="form-control" 
                                                placeholder="Catatan / feedback untuk mahasiswa (opsional)..." 
                                                rows="2" 
                                                style="font-size: 0.85rem; padding: 0.35rem 0.5rem;"
                                            >{{ old('catatan_dosen', $sub->catatan_dosen) }}</textarea>
                                        </form>
                                    @else
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection