<?php
// Tugas Rumah Pertemuan 5: Mini Application OOP (Advanced OOP)
// Domain : Sistem Peminjaman Ruangan Kampus
// Konsep : Inheritance, Overriding, Polymorphism, Abstract class, Interface, Composition

if (PHP_SAPI !== "cli") {
    echo "<pre>";   // supaya output rapi saat dibuka di browser (XAMPP)
}

// ===== INTERFACE (can-do): kontrak untuk apa pun yang bisa dipinjam =====
interface BisaDipinjam {
    public function getNama(): string;
    public function maksDurasiJam(): int;
    public function syarat(): string;
}

// ===== ABSTRACT CLASS + INHERITANCE (is-a): semua jenis ruangan =====
abstract class Ruangan implements BisaDipinjam {
    protected string $kode;
    protected string $nama;
    protected int $kapasitas;

    public function __construct(string $kode, string $nama, int $kapasitas) {
        if ($kapasitas < 1) {
            throw new InvalidArgumentException("Kapasitas minimal 1 orang");
        }
        $this->kode      = $kode;
        $this->nama      = $nama;
        $this->kapasitas = $kapasitas;
    }

    public function getNama(): string {
        return $this->nama;
    }

    // Perilaku bawaan: boleh di-override oleh subclass
    public function maksDurasiJam(): int {
        return 4;
    }

    // Wajib diwujudkan oleh setiap subclass
    abstract public function jenis(): string;
    abstract public function syarat(): string;

    public function info(): string {
        return $this->jenis() . " $this->nama (kode $this->kode, kapasitas $this->kapasitas orang)";
    }
}

class RuangKelas extends Ruangan {
    public function jenis(): string {
        return "Ruang Kelas";
    }

    public function syarat(): string {
        return "Pengajuan cukup lewat sistem";
    }
}

class Laboratorium extends Ruangan {
    public function jenis(): string {
        return "Laboratorium";
    }

    // OVERRIDING: lab dibatasi lebih singkat daripada bawaan (4 jam)
    public function maksDurasiJam(): int {
        return 3;
    }

    public function syarat(): string {
        return "Wajib didampingi laboran dan membawa surat pengantar dosen";
    }
}

class Aula extends Ruangan {
    public function jenis(): string {
        return "Aula";
    }

    // OVERRIDING: aula boleh dipakai lebih lama daripada bawaan (4 jam)
    public function maksDurasiJam(): int {
        return 8;
    }

    public function syarat(): string {
        return "Wajib melampirkan proposal kegiatan dan izin kemahasiswaan";
    }
}

// Proyektor bukan ruangan, jadi tidak cocok inheritance. Cukup implementasi interface.
class Proyektor implements BisaDipinjam {
    private string $merek;

    public function __construct(string $merek) {
        $this->merek = $merek;
    }

    public function getNama(): string {
        return "Proyektor $this->merek";
    }

    public function maksDurasiJam(): int {
        return 6;
    }

    public function syarat(): string {
        return "Dikembalikan ke petugas setelah dipakai";
    }
}

class Mahasiswa {
    private string $nim;
    private string $nama;

    public function __construct(string $nim, string $nama) {
        if (trim($nim) === "" || trim($nama) === "") {
            throw new InvalidArgumentException("NIM dan nama mahasiswa tidak boleh kosong");
        }
        $this->nim  = $nim;
        $this->nama = $nama;
    }

    public function getNim(): string {
        return $this->nim;
    }

    public function getNama(): string {
        return $this->nama;
    }
}

// ===== COMPOSITION (has-a): Peminjaman tersusun dari objek lain =====
class Peminjaman {
    private string $idPeminjaman;
    private Mahasiswa $peminjam;          // has-a Mahasiswa
    private BisaDipinjam $objekUtama;      // has-a objek yang dipinjam (ruangan)
    private array $alatTambahan = [];      // has-many BisaDipinjam (misal proyektor)
    private string $tanggal;
    private string $jamMulai;
    private string $jamSelesai;
    private string $status = "menunggu";

    public function __construct(
        string $idPeminjaman,
        Mahasiswa $peminjam,
        BisaDipinjam $objekUtama,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai
    ) {
        $this->validasiJadwal($tanggal, $jamMulai, $jamSelesai);
        $this->idPeminjaman = $idPeminjaman;
        $this->peminjam     = $peminjam;
        $this->objekUtama   = $objekUtama;
        $this->tanggal      = $tanggal;
        $this->jamMulai     = $jamMulai;
        $this->jamSelesai   = $jamSelesai;
        $this->cekDurasi($objekUtama);
    }

    public function tambahAlat(BisaDipinjam $alat): void {
        $this->cekDurasi($alat);
        $this->alatTambahan[] = $alat;
    }

    public function setujui(): void {
        $this->ubahStatus("disetujui");
    }

    public function tolak(): void {
        $this->ubahStatus("ditolak");
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function ringkasan(): string {
        $teks = "[$this->idPeminjaman] " . $this->peminjam->getNama() . " meminjam "
              . $this->objekUtama->getNama() . " pada $this->tanggal pukul $this->jamMulai-$this->jamSelesai"
              . " (status: $this->status)\n";
        if (count($this->alatTambahan) > 0) {
            $nama = array_map(fn(BisaDipinjam $a) => $a->getNama(), $this->alatTambahan);
            $teks .= "    Alat tambahan: " . implode(", ", $nama) . "\n";
        }
        return $teks;
    }

    // POLYMORPHISM: tidak ada if/else berdasarkan jenis objek.
    // maksDurasiJam() otomatis memakai aturan milik objek yang sedang diperiksa.
    private function cekDurasi(BisaDipinjam $objek): void {
        $durasiMenit = $this->keMenit($this->jamSelesai) - $this->keMenit($this->jamMulai);
        if ($durasiMenit > $objek->maksDurasiJam() * 60) {
            throw new RuntimeException($objek->getNama() . " maksimal " . $objek->maksDurasiJam() . " jam per peminjaman");
        }
    }

    private function ubahStatus(string $statusBaru): void {
        if ($this->status !== "menunggu") {
            throw new RuntimeException("Peminjaman $this->idPeminjaman berstatus $this->status, tidak bisa diubah menjadi $statusBaru");
        }
        $this->status = $statusBaru;
    }

    private function keMenit(string $jam): int {
        [$j, $m] = explode(":", $jam);
        return (int)$j * 60 + (int)$m;
    }

    private function validasiJadwal(string $tanggal, string $jamMulai, string $jamSelesai): void {
        $d = DateTime::createFromFormat("Y-m-d", $tanggal);
        if ($d === false || $d->format("Y-m-d") !== $tanggal) {
            throw new InvalidArgumentException("Tanggal tidak valid, gunakan format YYYY-MM-DD");
        }
        $pola = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if (!preg_match($pola, $jamMulai) || !preg_match($pola, $jamSelesai)) {
            throw new InvalidArgumentException("Format jam tidak valid, gunakan HH:MM");
        }
        if ($jamMulai >= $jamSelesai) {
            throw new InvalidArgumentException("Jam selesai harus lebih besar dari jam mulai");
        }
    }
}

// ===== DEMO =====
// Fungsi ini menerima TIPE INTERFACE, bukan class konkret (polymorphism)
function tampilkanSyarat(BisaDipinjam $item): void {
    echo "- " . str_pad($item->getNama(), 26) . " | maks " . $item->maksDurasiJam() . " jam | " . $item->syarat() . "\n";
}

function jalankanTes(int $no, string $judul, bool $harusError, callable $uji): void {
    echo "Skenario $no: $judul\n";
    try {
        $uji();
        echo $harusError ? "  HASIL: GAGAL, seharusnya ditolak\n\n" : "  HASIL: OK\n\n";
    } catch (Throwable $e) {
        $pesan = explode(", called in", $e->getMessage())[0];
        echo "  Pesan: $pesan\n";
        echo $harusError ? "  HASIL: OK (ditolak sesuai harapan)\n\n" : "  HASIL: GAGAL (error tak terduga)\n\n";
    }
}

$daftar = [
    new RuangKelas("RK-101", "A101", 40),
    new Laboratorium("LAB-01", "Lab Komputer", 30),
    new Aula("AULA-01", "Aula Utama", 300),
    new Proyektor("Epson EB-X06"),
];

echo "=== DEMO POLYMORPHISM: satu kontrak BisaDipinjam, banyak perilaku ===\n";
foreach ($daftar as $item) {
    tampilkanSyarat($item);
}
echo "\n=== PENGUJIAN MINI APPLICATION ===\n\n";

jalankanTes(1, "Alur normal: Lab 2 jam + proyektor, lalu disetujui", false, function () use ($daftar) {
    $p = new Peminjaman("P001", new Mahasiswa("2025100001", "Budi"), $daftar[1], "2026-10-26", "09:00", "11:00");
    $p->tambahAlat($daftar[3]);
    $p->setujui();
    echo "  " . $p->ringkasan();
});

jalankanTes(2, "Batas durasi aula (8 jam) masih diterima", false, function () use ($daftar) {
    $p = new Peminjaman("P002", new Mahasiswa("2025100002", "Sari"), $daftar[2], "2026-10-27", "08:00", "16:00");
    echo "  " . $p->ringkasan();
});

jalankanTes(3, "Lab 4 jam melebihi batas lab (3 jam)", true, function () use ($daftar) {
    new Peminjaman("P003", new Mahasiswa("2025100003", "Andi"), $daftar[1], "2026-10-28", "09:00", "13:00");
});

jalankanTes(4, "Aula 8 jam + proyektor melebihi batas proyektor (6 jam)", true, function () use ($daftar) {
    $p = new Peminjaman("P004", new Mahasiswa("2025100004", "Dewi"), $daftar[2], "2026-10-29", "08:00", "16:00");
    $p->tambahAlat($daftar[3]);
});

jalankanTes(5, "Abstract class Ruangan tidak boleh dibuat langsung", true, function () {
    new Ruangan("X-01", "Ruang Bebas", 10);
});

jalankanTes(6, "Objek yang bukan BisaDipinjam ditolak oleh kontrak", true, function () use ($daftar) {
    $p = new Peminjaman("P006", new Mahasiswa("2025100006", "Rina"), $daftar[0], "2026-10-30", "10:00", "12:00");
    $p->tambahAlat(new Mahasiswa("2025100007", "Fajar"));
});
