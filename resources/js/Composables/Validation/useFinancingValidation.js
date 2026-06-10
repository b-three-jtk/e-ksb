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

    const validateStep1 = () => {
        const errs = {}
        const m = form.member

        if (!m.user_code)
            errs.user_code = 'Nomor anggota wajib dipilih.'

        if (!m.name?.trim())
            errs.name = 'Nama lengkap wajib diisi.'

        if (!m.nik)
            errs.nik = 'NIK wajib diisi.'
        else if (!isValidNik(m.nik))
            errs.nik = 'NIK harus 16 digit angka.'

        if (!isValidEmail(m.email))
            errs.email = 'Format email tidak valid.'

        if (!m.phone_number)
            errs.phone_number = 'Nomor telepon wajib diisi.'
        else if (!isValidPhone(m.phone_number))
            errs.phone_number = 'Nomor telepon harus 8-14 digit angka.'

        if (!m.gender)
            errs.gender = 'Jenis kelamin wajib dipilih.'

        if (!m.residential_address?.trim())
            errs.residential_address = 'Alamat wajib diisi.'

        if (!form.member.heirs || form.member.heirs.length === 0)
            errs.heirs = 'Minimal satu data ahli waris wajib ditambahkan.'

        if (form.financing.status !== 'Belum Ditinjau') {
            if (m.is_have_eligible_saving === false)
                errs.eligible_saving = 'Pemohon belum memiliki tabungan yang memenuhi syarat.'
            if (m.is_have_no_obligation === false)
                errs.no_obligation = 'Pemohon masih memiliki kewajiban pembiayaan aktif.'
        }

        return errs
    }

    const validateStep2 = () => {
        const errs = {}
        const m = form.member

        if (!m.job_title?.trim())
            errs.job_title = 'Jabatan wajib diisi.'

        if (!m.company_or_business_name?.trim())
            errs.company_or_business_name = 'Nama perusahaan/bisnis wajib diisi.'

        if (!m.business_field?.trim())
            errs.business_field = 'Bidang pekerjaan wajib diisi.'

        if (!m.tenure_year && m.tenure_year !== 0)
            errs.tenure_year = 'Lama bekerja wajib diisi.'

        if (!m.workplace_contact?.trim())
            errs.workplace_contact = 'Kontak perusahaan wajib diisi.'
        else if (!isValidPhone(m.workplace_contact))
            errs.workplace_contact = 'Kontak perusahaan harus 8-13 digit angka.'

        if (!m.workplace_address?.trim())
            errs.workplace_address = 'Alamat perusahaan wajib diisi.'

        if (!form.income_slip_file && !form.documents?.income_slip)
            errs.income_slip_file = 'Slip gaji wajib diunggah.'

        if (!form.bank_book_file && !form.documents?.bank_book)
            errs.bank_book_file = 'Foto buku tabungan wajib diunggah.'

        return errs
    }

    const validateStep3 = () => {
        const errs = {}

        if (!form.financing.name?.trim())
            errs.financing_name = 'Nama objek pembiayaan wajib diisi.'

        if (!form.collateral.collateral_type)
            errs.collateral_type = 'Jenis jaminan wajib dipilih.'

        return errs
    }

    const validateStep4 = () => {
        const errs = {}

        if (!form.financing.supplier_id)
            errs.supplier_name = 'Supplier wajib diisi.'

        if (!form.financing.cost_price)
            errs.cost_price = 'Harga pokok wajib diisi.'

        if (!form.purchase_receipt_file && !form.documents?.purchase_receipt)
            errs.purchase_receipt_file = 'Nota pembelian wajib diunggah.'

        return errs
    }

    const validateStep5 = () => {
        const errs = {}

        if (form.financing.status !== 'Disetujui')
            errs.status = 'Status pembiayaan harus Disetujui sebelum finalisasi.'

        if (!form.financing.akad_date)
            errs.akad_date = 'Tanggal akad wajib diisi.'

        if (!form.akad_document_file && !form.documents?.akad_document)
            errs.akad_document_file = 'Dokumen akad wajib diunggah.'

        if (!form.financing.payment_method)
            errs.payment_method = 'Metode pembayaran wajib dipilih.'

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
        1: ['user_code', 'name', 'nik', 'email', 'phone_number', 'gender',
            'residential_address', 'heirs', 'eligible_saving', 'no_obligation'],
        2: ['job_title', 'company_or_business_name', 'business_field',
            'tenure_year', 'workplace_contact', 'workplace_address',
            'income_slip_file', 'bank_book_file'],
        3: ['financing_name', 'collateral_type'],
        4: ['supplier_name', 'cost_price', 'purchase_receipt_file'],
        5: ['status', 'akad_date', 'akad_document_file', 'payment_method'],
    }
    return map[step] ?? []
}
