import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import { useForm } from '@inertiajs/vue3'

export function useFinancingForm(initialData = null) {
    // State
    const searchQuery = ref('')
    const anggotaResults = ref([])
    const isLoadingSearch = ref(false)
    const selectedAnggota = ref(null)
    const isAnggotaSelected = ref(false)

    const searchPemasokQuery = ref('')
    const pemasokResults = ref([])
    const isLoadingSearchPemasok = ref(false)
    const selectedPemasok = ref(null)
    const isPemasokSelected = ref(false)

    const form = useForm({
        // Anggota data
        anggota: {
            kode_pengguna: initialData?.anggota?.kode_pengguna || '',
            name: initialData?.anggota?.nama || '',
            nik: initialData?.anggota?.nik || '',
            email: initialData?.anggota?.email || '',
            no_telp: initialData?.anggota?.no_telp || '',
            jenis_kelamin: initialData?.anggota?.jenis_kelamin || '',
            tempat_lahir: initialData?.anggota?.tempat_lahir || '',
            tgl_lahir: initialData?.anggota?.tgl_lahir || '',
            pendidikan_terakhir: initialData?.anggota?.pendidikan_terakhir || '',
            alamat_domisili: initialData?.anggota?.alamat_domisili || '',
            alamat_ktp: initialData?.anggota?.alamat_ktp || '',
            status_pernikahan: initialData?.anggota?.status_pernikahan || '',
            jml_tanggungan: initialData?.anggota?.jml_tanggungan || 0,

            employment_status: initialData?.anggota?.employment_status || '',
            job_title: initialData?.anggota?.job_title || '',
            company_or_business_name: initialData?.anggota?.company_or_business_name || '',
            business_field: initialData?.anggota?.business_field || '',
            tenure_year: initialData?.anggota?.tenure_year || 0,
            workplace_address: initialData?.anggota?.workplace_address || '',
            workplace_contact: initialData?.anggota?.workplace_contact || '',

            gaji_pokok_amount: initialData?.anggota?.gaji_pokok_amount || '',
            penghasilan_usaha_amount: initialData?.anggota?.penghasilan_usaha_amount || '',
            penghasilan_pasangan_amount: initialData?.anggota?.penghasilan_pasangan_amount || '',
            penghasilan_lainnya_amount: initialData?.anggota?.penghasilan_lainnya_amount || '',
            biaya_hidup_keluarga_amount: initialData?.anggota?.biaya_hidup_keluarga_amount || '',
            biaya_pendidikan_amount: initialData?.anggota?.biaya_pendidikan_amount || '',
            jumlah_cicilan_amount: initialData?.anggota?.jumlah_cicilan_amount || '',
            jumlah_biaya_lainnya_amount: initialData?.anggota?.jumlah_biaya_lainnya_amount || '',

            is_have_eligible_saving: initialData?.anggota?.is_have_eligible_saving || null,
            is_have_no_obligation: initialData?.anggota?.is_have_no_obligation || null,
            heirs: initialData?.anggota?.heirs || [],
        },
        // Pembiayaan data
        pembiayaan: {
            name: initialData?.pembiayaan?.name || '',
            jenis_barang_id: initialData?.pembiayaan?.jenis_barang_id || null,
            brand: initialData?.pembiayaan?.brand || '',
            condition: initialData?.pembiayaan?.condition || '',
            qty: initialData?.pembiayaan?.qty || null,
            specification: initialData?.pembiayaan?.specification || '',
            price_per_unit: initialData?.pembiayaan?.price_per_unit || '',
            harga_perolehan: initialData?.pembiayaan?.harga_perolehan || null,
            margin_keuntungan: initialData?.pembiayaan?.margin_keuntungan || null,
            akad_wakalah_date: initialData?.pembiayaan?.akad_wakalah_date || null,
            metode_pembayaran: initialData?.pembiayaan?.metode_pembayaran || '',
            tgl_akad: initialData?.pembiayaan?.tgl_akad || '',
            uang_muka: initialData?.pembiayaan?.uang_muka || null,
            status: initialData?.pembiayaan?.status || 'Menunggu Kelengkapan Dokumen',
            purchase_receipt: initialData?.pembiayaan?.purchase_receipt || null,
            tenor: initialData?.pembiayaan?.tenor || null,
            harga_perkiraan: initialData?.pembiayaan?.harga_perkiraan || null,
            pemasok_id: initialData?.pembiayaan?.pemasok_id || null,
            tangguh_payment_date: initialData?.pembiayaan?.tangguh_payment_date || null,
        },
        collateral: {
            collateral_type: initialData?.collateral?.collateral_type || '',
            owner_name: initialData?.collateral?.owner_name || '',
            estimated_market_value: initialData?.collateral?.estimated_market_value || 0,
            collateral_location: initialData?.collateral?.collateral_location || '',
        },
        verification: initialData?.verification || [],
        documents: {
            family_card: initialData?.documents?.family_card || null,
            income_slip: initialData?.documents?.income_slip || null,
            bank_book: initialData?.documents?.bank_book || null,
            purchase_receipt: initialData?.documents?.purchase_receipt || null,
            akad_document: initialData?.documents?.akad_document || null,
            akad_wakalah_document: initialData?.documents?.akad_wakalah_document || null
        },
        // Pemasok data
        pemasok: {
            nama_pemasok: initialData?.pemasok?.nama_pemasok || '',
            alamat_pemasok: initialData?.pemasok?.alamat_pemasok || '',
            contact: initialData?.pemasok?.contact || '',
        },
        // Local state untuk temporary input
        monthly_installment: null,
        monthly_income: null,
        income_type: '',
        income_amount: '',
        expense_type: '',
        expense_amount: '',
        income_slip_file: null,
        bank_book_file: null,
        purchase_receipt_file: null,
        akad_document_file: null,
        akad_wakalah_file: null
    })


    // Search anggota
    let searchTimeout = null

    watch(() => searchQuery.value, (query) => {
        // 1. Bersihkan timer sebelumnya setiap kali user mengetik karakter baru
        if (searchTimeout) {
            clearTimeout(searchTimeout)
        }

        if (!query || query.length < 2) {
            anggotaResults.value = []
            return
        }

        // 2. Buat timer baru
        searchTimeout = setTimeout(async () => {
            isLoadingSearch.value = true
            try {
                const response = await axios.get('/admin/anggota/search', {
                    params: { q: query }
                })
                anggotaResults.value = response.data.anggota
            } catch (error) {
                console.error('Error searching anggota:', error)
                anggotaResults.value = []
            } finally {
                isLoadingSearch.value = false
            }
        }, 500) // 500ms delay
    })

    // Pilih anggota
    const selectAnggota = (anggota) => {
        selectedAnggota.value = anggota
        searchQuery.value = anggota.nama

        console.log('anggota:',anggota);

        // Update anggota form
        form.anggota.kode_pengguna = anggota.user.kode_pengguna || ''
        form.anggota.nama = anggota.user.nama || ''
        form.anggota.nik = anggota.user.nik || ''
        form.anggota.email = anggota.user.email || ''
        form.anggota.no_telp = anggota.user.no_telp || ''
        form.anggota.jenis_kelamin = anggota.jenis_kelamin || ''
        form.anggota.tempat_lahir = anggota.tempat_lahir || ''
        form.anggota.tgl_lahir = anggota.tgl_lahir || ''
        form.anggota.pendidikan_terakhir = anggota.pendidikan_terakhir || ''
        form.anggota.alamat_domisili = anggota.alamat_domisili || ''
        form.anggota.alamat_ktp = anggota.alamat_ktp || ''
        form.anggota.status_pernikahan = anggota.status_pernikahan || ''
        form.anggota.jml_tanggungan = anggota.jml_tanggungan || 0

        form.anggota.employment_status = anggota.member_jobs?.employment_status || ''
        form.anggota.job_title = anggota.member_jobs?.job_title || ''
        form.anggota.company_or_business_name = anggota.member_jobs?.company_or_business_name || ''
        form.anggota.business_field = anggota.member_jobs?.business_field || ''
        form.anggota.tenure_year = anggota.member_jobs?.tenure_year || 0
        form.anggota.workplace_address = anggota.member_jobs?.workplace_address || ''
        form.anggota.workplace_contact = anggota.member_jobs?.workplace_contact || ''

        form.anggota.gaji_pokok_amount = anggota.financials?.gaji_pokok_amount || ''
        form.anggota.penghasilan_usaha_amount = anggota.financials?.penghasilan_usaha_amount || ''
        form.anggota.penghasilan_pasangan_amount = anggota.financials?.penghasilan_pasangan_amount || ''
        form.anggota.penghasilan_lainnya_amount = anggota.financials?.penghasilan_lainnya_amount || ''
        form.anggota.biaya_hidup_keluarga_amount = anggota.financials?.biaya_hidup_keluarga_amount || ''
        form.anggota.biaya_pendidikan_amount = anggota.financials?.biaya_pendidikan_amount || ''
        form.anggota.jumlah_cicilan_amount = anggota.financials?.jumlah_cicilan_amount || ''
        form.anggota.jumlah_biaya_lainnya_amount = anggota.financials?.jumlah_biaya_lainnya_amount || ''

        form.anggota.is_have_eligible_saving = anggota.is_have_eligible_saving || false
        form.anggota.is_have_no_obligation = anggota.is_have_no_obligation || false

        form.documents.family_card = anggota.family_card || null,
        form.documents.income_slip = anggota.income_slip || null,
        form.documents.bank_book = anggota.bank_book || null,

        form.anggota.heirs = anggota.heirs || []

        anggotaResults.value = []
        isAnggotaSelected.value = true
    }

    const resetAnggotaSelection = () => {
        selectedAnggota.value = null
        searchQuery.value = ''
        form.anggota = {
            kode_pengguna: '',
            nama: '',
            nik: '',
            email: '',
            no_telp: '',
            jenis_kelamin: '',
            tempat_lahir: '',
            tgl_lahir: '',
            pendidikan_terakhir: '',
            alamat_domisili: '',
            alamat_ktp: '',
            status_pernikahan: '',
            jml_tanggungan: null,

            employment_status: '',
            job_title: '',
            company_or_business_name: '',
            business_field: '',
            tenure_year: null,
            workplace_address: '',
            workplace_contact: '',

            gaji_pokok_amount: '',
            penghasilan_usaha_amount: '',
            penghasilan_pasangan_amount: '',
            penghasilan_lainnya_amount: '',
            biaya_hidup_keluarga_amount: '',
            biaya_pendidikan_amount: '',
            jumlah_cicilan_amount: '',
            jumlah_biaya_lainnya_amount: '',

            is_have_eligible_saving: null,
            is_have_no_obligation: null,

            heirs: [],
        }
        form.pembiayaan = {
            name: '',
            jenis_barang_id: null,
            brand: '',
            condition: '',
            qty: null,
            specification: '',
            harga_perolehan: null,
            margin_keuntungan: null,
            is_wakalah: false,
            metode_pembayaran: '',
            tgl_akad: '',
            uang_muka: null,
            notes: '',
            status: '',
            pemasok_id: null,
        }
        form.collateral = {
            collateral_type: '',
            owner_name: '',
            estimated_market_value: null,
            collateral_location: '',
        }
        form.pemasok = {
            nama_pemasok: '',
            alamat_pemasok: '',
            contact: '',
        }
        isAnggotaSelected.value = false
    }

    // search pemasok
    let pemasokSearchTimeout = null
    watch(() => searchPemasokQuery.value, (query) => {
        // 1. Bersihkan timer sebelumnya setiap kali user mengetik karakter baru
        if (pemasokSearchTimeout) {
            clearTimeout(pemasokSearchTimeout)
        }

        if (!query || query.length < 2) {
            pemasokResults.value = []
            return
        }

        // 2. Buat timer baru
        pemasokSearchTimeout = setTimeout(async () => {
            isLoadingSearch.value = true
            try {
                const response = await axios.get('/admin/pemasok/search', {
                    params: { q: query }
                })
                pemasokResults.value = response.data.pemasok
            } catch (error) {
                console.error('Error searching pemasok:', error)
                pemasokResults.value = []
            } finally {
                isLoadingSearch.value = false
            }
        }, 500) // 500ms delay
    })

    // Pilih pemasok
    const selectPemasok = (pemasok) => {
        selectedPemasok.value = pemasok
        searchPemasokQuery.value = pemasok.nama_pemasok

        form.pemasok.nama_pemasok = pemasok.nama_pemasok || ''
        form.pemasok.alamat_pemasok = pemasok.alamat_pemasok || ''
        form.pemasok.contact = pemasok.contact || ''

        pemasokResults.value = []
        isPemasokSelected.value = true
    }

    const resetPemasokSelection = () => {
        selectedPemasok.value = null
        searchPemasokQuery.value = ''
        form.pemasok = {
            nama_pemasok: '',
            alamat_pemasok: '',
            contact: '',
        }
        isPemasokSelected.value = false
    }

    // Heirs
    const addHeir = (heirData) => {
        if (!heirData.heir_nik || !heirData.heir_name || !heirData.relationship || !heirData.heir_contact) {
            toast('Lengkapi semua field untuk menambahkan ahli waris!', {
                type: 'error',
                position: 'bottom-right',
            })
            return
        }

        form.anggota.heirs.push({
            heir_nik: heirData.heir_nik,
            heir_name: heirData.heir_name,
            relationship: heirData.relationship,
            heir_contact: heirData.heir_contact,
        })
    }

    const removeHeir = (index) => {
        form.anggota.heirs.splice(index, 1)
    }

    const submit = () => {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin mengirim permohonan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#009141',
        }).then((result) => {
            if (result.isConfirmed) {
                form.post('/admin/pembiayaan/store', {
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            toast(page.props.flash.success, {
                                type: 'success',
                                position: 'bottom-right',
                            })
                        }
                    },
                    onError: (errors) => {
                        // Show all errors
                        const errorMessages = Object.values(errors).flat()

                        if (errorMessages.length > 0) {
                            toast(errorMessages.join(', '), {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        } else {
                            toast('Gagal menyimpan permohonan', {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        }
                    }
                })
            }
        })
    }

    const finalize = () => {
        if (form.pembiayaan.metode_pembayaran === 'Cicilan') {
            form.pembiayaan.status = 'Angsuran Berjalan'
        } else if (form.pembiayaan.metode_pembayaran === 'Tangguh') {
            form.pembiayaan.status = 'Pembayaran Tangguh'
        } else {
            form.pembiayaan.status = 'Lunas'
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin memfinalisasi pembiayaan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#009141',
        }).then((result) => {
            if (result.isConfirmed) {
                form.post('/admin/pembiayaan/finalize', {
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            toast(page.props.flash.success, {
                                type: 'success',
                                position: 'bottom-right',
                            })
                        }
                    },
                    onError: (errors) => {
                        // Show all errors
                        form.pembiayaan.status = 'Disetujui' // Revert status if error occurs
                        const errorMessages = Object.values(errors).flat()

                        if (errorMessages.length > 0) {
                            toast(errorMessages.join(', '), {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        } else {
                            toast('Gagal menyimpan permohonan', {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        }
                    }
                })
            } else {
                form.pembiayaan.status = 'Disetujui'
            }
        })
    }

    const saveDraft = () => {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin menyimpan sementara permohonan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#009141',
        }).then((result) => {
            if (result.isConfirmed) {
                form.post('/admin/pembiayaan/draft', {
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            toast(page.props.flash.success, {
                                type: 'success',
                                position: 'bottom-right',
                            })
                        }
                    },
                    onError: (errors) => {
                        // Show all errors
                        const errorMessages = Object.values(errors).flat()

                        if (errorMessages.length > 0) {
                            toast(errorMessages.join(', '), {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        } else {
                            toast('Gagal menyimpan permohonan', {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        }
                    }
                })
            }
        })
    }

    onMounted(() => {
    if (initialData?.anggota) {
            isAnggotaSelected.value = true
            selectedAnggota.value = initialData.anggota
            searchQuery.value = initialData.anggota.name
        }
    })

    return {
        // State
        form,
        searchQuery,
        anggotaResults,
        isLoadingSearch,
        selectedAnggota,
        isAnggotaSelected,
        searchPemasokQuery,
        pemasokResults,
        isLoadingSearchPemasok,
        selectedPemasok,
        isPemasokSelected,
        // Methods
        resetPemasokSelection,
        resetAnggotaSelection,
        selectAnggota,
        selectPemasok,
        addHeir,
        removeHeir,
        submit,
        saveDraft,
        finalize
    }
}
