import { ref, computed } from 'vue'
import { toast } from 'vue3-toastify'

// Label per step untuk pesan toast
const stepLabels = {
    1: 'Identitas Pribadi',
    2: 'Data Keuangan',
    3: 'Objek Pembiayaan',
    4: 'Data Pengadaan',
    5: 'Finalisasi',
}

export function useFinancingValidation(form) {
    const touchedSteps = ref(new Set())
    const fieldErrors = ref({})

    // Helper validators
    const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    const isValidPhone = (phone) => /^[0-9]{8,14}$/.test(phone)
    const isValidNik   = (nik)   => /^[0-9]{16}$/.test(nik)
    const isValidDependents = (jml_tanggungan) => /^[0-9]+$/.test(jml_tanggungan)

    const validateStep1 = () => {
        const errs = {}
        const m = form.anggota

        if (!m.kode_pengguna)
            errs.kode_pengguna = 'Nomor anggota wajib dipilih.'

        if (!m.nama?.trim())
            errs.nama = 'Nama lengkap wajib diisi.'

        if (!m.nik)
            errs.nik = 'NIK wajib diisi.'
        else if (!isValidNik(m.nik))
            errs.nik = 'NIK harus 16 digit angka.'

        if (!isValidEmail(m.email))
            errs.email = 'Format email tidak valid.'

        if (!/^62\d{10,20}$/.test(m.no_telp)) {
            errs.no_telp = "Nomor telepon wajib diawali 62 dan minimal 10 digit";
        }

        if (!m.jenis_kelamin)
            errs.jenis_kelamin = 'Jenis kelamin wajib dipilih.'

        if (!isValidDependents(m.jml_tanggungan))
            errs.jml_tanggungan = 'Jumlah tanggungan harus angka.'

        if (!form.anggota.ahli_waris || form.anggota.ahli_waris.length === 0) {
            errs.ahli_waris = 'Minimal satu data ahli waris wajib ditambahkan.'
        } else {
            const hasInvalidAhliWaris = form.anggota.ahli_waris.some(heir => {
                return !heir.nik_ahli_waris || !isValidNik(heir.nik_ahli_waris) ||
                       !heir.nama_ahli_waris?.trim() ||
                       !heir.hubungan?.trim() ||
                       !heir.kontak_ahli_waris || !isValidPhone(heir.kontak_ahli_waris);
            });

            if (hasInvalidAhliWaris) {
                errs.ahli_waris = 'Pastikan NIK (16 digit), Nama, Hubungan, dan Kontak pada semua data ahli waris telah diisi dengan benar.';
            }
        }

        if (form.pembiayaan.status !== 'Belum Ditinjau') {
            if (m.is_have_eligible_saving === false)
                errs.eligible_saving = 'Pemohon belum memiliki tabungan yang memenuhi syarat.'
            if (m.is_have_no_obligation === false)
                errs.no_obligation = 'Pemohon masih memiliki kewajiban pembiayaan aktif.'
        }

        return errs
    }

    const validateStep2 = () => {
        const errs = {}
        const m = form.anggota
        const isValidTenureYear = (lama_bekerja) => /^[0-9]+$/.test(lama_bekerja)

        if (!m.status_pekerjaan?.trim())
            errs.status_pekerjaan = 'Status Pekerjaan wajib diisi.'

        if (m.lama_bekerja && !isValidTenureYear(m.lama_bekerja))
            errs.lama_bekerja = 'Lama bekerja harus berupa angka.'

        if (m.no_telp_kantor && !isValidPhone(m.no_telp_kantor))
            errs.no_telp_kantor = 'Kontak perusahaan harus 8-13 digit angka.'

        if (!form.income_slip_file && !form.documents?.income_slip)
            errs.income_slip_file = 'Slip gaji wajib diunggah.'
        else if (form.income_slip_file && !['image/jpeg', 'image/png'].includes(form.income_slip_file.type))
            errs.income_slip_file = 'Format slip gaji harus JPG, JPEG, atau PNG.'
        else if (form.income_slip_file && form.income_slip_file.size > 2 * 1024 * 1024)
            errs.income_slip_file = 'Ukuran slip gaji maksimal 2 MB.'

        if (!form.bank_book_file && !form.documents?.bank_book)
            errs.bank_book_file = 'Foto buku tabungan wajib diunggah.'
        else if (form.bank_book_file && !['image/jpeg', 'image/png'].includes(form.bank_book_file.type))
            errs.bank_book_file = 'Format buku tabungan harus JPG, JPEG, atau PNG.'
        else if (form.bank_book_file && form.bank_book_file.size > 2 * 1024 * 1024)
            errs.bank_book_file = 'Ukuran buku tabungan maksimal 2 MB.'

        return errs
    }

    const validateStep3 = () => {
        const errs = {}

        if (!form.pembiayaan.nama_barang?.trim())
            errs.nama_barang = 'Nama objek pembiayaan wajib diisi.'

        if (!form.pembiayaan.kondisi_produk)
            errs.kondisi_produk = 'Kondisi objek pembiayaan wajib diisi.'

        if (isNaN(form.pembiayaan.kuantitas) || form.pembiayaan.kuantitas <= 0)
            errs.kuantitas = 'Jumlah objek pembiayaan harus berupa angka positif.'
        else if (!form.pembiayaan.kuantitas)
            errs.kuantitas = 'Jumlah objek pembiayaan wajib diisi.'

        if (!form.pembiayaan.harga_perkiraan)
            errs.harga_perkiraan = 'Harga perkiraan wajib diisi.'

        if (!form.pembiayaan.spesifikasi_barang?.trim())
            errs.spesifikasi_barang = 'Spesifikasi objek pembiayaan wajib diisi.'

        if (form.jaminan.jenis_jaminan?.trim()) {
            if (!form.jaminan.nama_pemilik?.trim()) {
                errs.nama_pemilik = 'Atas nama pemilik jaminan wajib diisi.';
            }
            if (!form.jaminan.nilai_perkiraan_pasar) {
                errs.nilai_perkiraan_pasar = 'Nilai perkiraan pasar wajib diisi.';
            }
            if (!form.jaminan.lokasi_kondisi_jaminan?.trim()) {
                errs.lokasi_kondisi_jaminan = 'Lokasi/kondisi jaminan wajib diisi.';
            }
        }

        if (form.jaminan.nilai_perkiraan_pasar && form.jaminan.nilai_perkiraan_pasar > form.pembiayaan.harga_perkiraan) {
            errs.nilai_perkiraan_pasar = 'Nilai jaminan harus kurang dari atau sama dengan perkiraan harga objek.'
        }

        return errs
    }

    const validateStep4 = () => {
        const errs = {}

        if (!form.pembiayaan.pemasok_id)
            errs.nama_pemasok = 'Pemasok wajib diisi.'

        if (!form.pembiayaan.harga_perolehan)
            errs.harga_perolehan = 'Harga pokok wajib diisi.'

        if (!form.pembiayaan.harga_beli_per_unit)
            errs.harga_beli_per_unit = 'Harga per unit wajib diisi.'

        if (!form.purchase_receipt_file && !form.documents?.struk_pembelian)
            errs.purchase_receipt_file = 'Nota pembelian wajib diunggah.'
        else if (form.purchase_receipt_file && !['image/jpeg', 'image/png'].includes(form.purchase_receipt_file.type))
            errs.purchase_receipt_file = 'Format nota pembelian harus JPG, JPEG, atau PNG.'
        else if (form.purchase_receipt_file && form.purchase_receipt_file.size > 2 * 1024 * 1024)
            errs.purchase_receipt_file = 'Ukuran nota pembelian maksimal 2 MB.'

        if (form.is_wakalah) {
            if (!form.akad_wakalah_file && !form.documents?.akad_wakalah)
                errs.akad_wakalah_file = 'Dokumen akad wakalah wajib diunggah.'
            else if (form.akad_wakalah_file && !['application/pdf'].includes(form.akad_wakalah_file.type))
                errs.akad_wakalah_file = 'Format dokumen akad wakalah harus PDF.'
            else if (form.akad_wakalah_file && form.akad_wakalah_file.size > 2 * 1024 * 1024)
                errs.akad_wakalah_file = 'Ukuran dokumen akad wakalah maksimal 2 MB.'

            if (!form.pembiayaan.akad_wakalah_date || form.pembiayaan.akad_wakalah_date === '')
                errs.akad_wakalah_date = 'Tanggal akad wakalah wajib diisi.'
        }

        return errs
    }

    const validateStep5 = () => {
        const errs = {}

        if (form.pembiayaan.status !== 'Disetujui')
            errs.status = 'Status pembiayaan harus Disetujui sebelum finalisasi.'

        if (!form.pembiayaan.tgl_akad)
            errs.tgl_akad = 'Tanggal akad wajib diisi.'

        if (!form.akad_document_file && !form.documents?.akad_document)
            errs.akad_document_file = 'Dokumen akad wajib diunggah.'

        if (!form.pembiayaan.metode_pembayaran)
            errs.metode_pembayaran = 'Metode pembayaran wajib dipilih.'

        if (form.pembiayaan.metode_pembayaran === 'Tangguh') {
            if (!form.pembiayaan.tangguh_tgl_pembayaran)
                errs.tangguh_tgl_pembayaran = 'Tanggal pembayaran tangguh wajib diisi.'
            else if (new Date(form.pembiayaan.tangguh_tgl_pembayaran) <= new Date(form.pembiayaan.tgl_akad))
                errs.tangguh_tgl_pembayaran = 'Tanggal pembayaran tangguh harus setelah tanggal akad.'
        }

        return errs
    }

    const validators = {
        1: validateStep1,
        2: validateStep2,
        3: validateStep3,
        4: validateStep4,
        5: validateStep5,
    }

    /**
     * Validasi step & tampilkan toast jika ada error.
     * Return true kalau valid, false kalau ada error.
     */
    const validateAndShowErrors = (step) => {
        touchedSteps.value.add(step)

        const freshErrs = validators[step]?.() ?? {}

        // Bersihkan error lama untuk step ini, lalu isi dengan yang baru
        getAllKeysForStep(step).forEach(k => { delete fieldErrors.value[k] })
        Object.assign(fieldErrors.value, freshErrs)

        const errorList = Object.values(freshErrs)

        if (errorList.length > 0) {
            // Tampilkan error pertama di toast; sisanya sudah muncul inline di field
            toast(`${stepLabels[step]}: ${errorList[0]}`, {
                type: 'error',
                position: 'bottom-right',
                autoClose: 4000,
            })
            return false
        }

        return true
    }

    /**
     * Hapus error field secara live saat user mengetik.
     * Hanya aktif jika step sudah pernah di-submit (touched).
     */
    const validateField = (field, step) => {
        if (!touchedSteps.value.has(step)) return
        const errs = validators[step]?.() ?? {}
        if (errs[field]) {
            fieldErrors.value[field] = errs[field]
        } else {
            delete fieldErrors.value[field]
        }
    }

    const clearStepErrors = (step) => {
        getAllKeysForStep(step).forEach(k => { delete fieldErrors.value[k] })
    }

    const errors = computed(() => fieldErrors.value)

    const isStepValid = (step) => {
        const errs = validators[step]?.() ?? {}
        return Object.keys(errs).length === 0
    }

    return {
        errors,
        validateAndShowErrors,
        validateField,
        clearStepErrors,
        isStepValid,
        touchedSteps,
    }
}

function getAllKeysForStep(step) {
    const map = {
        1: ['kode_pengguna', 'name', 'nik', 'email', 'no_telp', 'jenis_kelamin',
            'alamat_ktp', 'ahli_waris', 'eligible_saving', 'no_obligation'],
        2: ['jabatan_pekerjaan', 'nama_perusahaan', 'bidang_usaha',
            'lama_bekerja', 'no_telp_kantor', 'alamat_tempat_bekerja',
            'income_slip_file', 'bank_book_file'],
        3: ['financing_name', 'jenis_jaminan'],
        4: ['nama_pemasok', 'harga_perolehan', 'purchase_receipt_file'],
        5: ['status', 'tgl_akad', 'akad_document_file', 'metode_pembayaran'],
    }
    return map[step] ?? []
}
