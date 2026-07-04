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

    const searchSupplierQuery = ref('')
    const supplierResults = ref([])
    const isLoadingSearchSupplier = ref(false)
    const selectedSupplier = ref(null)
    const isSupplierSelected = ref(false)

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
        // Financing data
        financing: {
            name: initialData?.financing?.name || '',
            jenis_barang_id: initialData?.financing?.jenis_barang_id || null,
            brand: initialData?.financing?.brand || '',
            condition: initialData?.financing?.condition || '',
            qty: initialData?.financing?.qty || null,
            specification: initialData?.financing?.specification || '',
            price_per_unit: initialData?.financing?.price_per_unit || '',
            cost_price: initialData?.financing?.cost_price || null,
            margin_amount: initialData?.financing?.margin_amount || null,
            akad_wakalah_date: initialData?.financing?.akad_wakalah_date || null,
            payment_method: initialData?.financing?.payment_method || '',
            akad_date: initialData?.financing?.akad_date || '',
            down_payment: initialData?.financing?.down_payment || null,
            status: initialData?.financing?.status || 'Menunggu Kelengkapan Dokumen',
            purchase_receipt: initialData?.financing?.purchase_receipt || null,
            tenor: initialData?.financing?.tenor || null,
            predicted_cost_price: initialData?.financing?.predicted_cost_price || null,
            supplier_id: initialData?.financing?.supplier_id || null,
            tangguh_payment_date: initialData?.financing?.tangguh_payment_date || null,
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
        // Supplier data
        supplier: {
            supplier_name: initialData?.supplier?.supplier_name || '',
            address: initialData?.supplier?.address || '',
            contact: initialData?.supplier?.contact || '',
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
        form.financing = {
            name: '',
            jenis_barang_id: null,
            brand: '',
            condition: '',
            qty: null,
            specification: '',
            cost_price: null,
            margin_amount: null,
            is_wakalah: false,
            payment_method: '',
            akad_date: '',
            down_payment: null,
            notes: '',
            status: '',
            supplier_id: null,
        }
        form.collateral = {
            collateral_type: '',
            owner_name: '',
            estimated_market_value: null,
            collateral_location: '',
        }
        form.supplier = {
            supplier_name: '',
            address: '',
            contact: '',
        }
        isAnggotaSelected.value = false
    }

    // search supplier
    let supplierSearchTimeout = null
    watch(() => searchSupplierQuery.value, (query) => {
        // 1. Bersihkan timer sebelumnya setiap kali user mengetik karakter baru
        if (supplierSearchTimeout) {
            clearTimeout(supplierSearchTimeout)
        }

        if (!query || query.length < 2) {
            supplierResults.value = []
            return
        }

        // 2. Buat timer baru
        supplierSearchTimeout = setTimeout(async () => {
            isLoadingSearch.value = true
            try {
                const response = await axios.get('/admin/suppliers/search', {
                    params: { q: query }
                })
                supplierResults.value = response.data.suppliers
            } catch (error) {
                console.error('Error searching suppliers:', error)
                supplierResults.value = []
            } finally {
                isLoadingSearch.value = false
            }
        }, 500) // 500ms delay
    })

    // Pilih supplier
    const selectSupplier = (supplier) => {
        selectedSupplier.value = supplier
        searchSupplierQuery.value = supplier.supplier_name

        form.supplier.supplier_name = supplier.supplier_name || ''
        form.supplier.address = supplier.address || ''
        form.supplier.contact = supplier.contact || ''

        supplierResults.value = []
        isSupplierSelected.value = true
    }

    const resetSupplierSelection = () => {
        selectedSupplier.value = null
        searchSupplierQuery.value = ''
        form.supplier = {
            supplier_name: '',
            address: '',
            contact: '',
        }
        isSupplierSelected.value = false
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
                form.post('/admin/financings/store', {
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
        if (form.financing.payment_method === 'Cicilan') {
            form.financing.status = 'Angsuran Berjalan'
        } else if (form.financing.payment_method === 'Tangguh') {
            form.financing.status = 'Pembayaran Tangguh'
        } else {
            form.financing.status = 'Lunas'
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
                form.post('/admin/financings/finalize', {
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
                        form.financing.status = 'Disetujui' // Revert status if error occurs
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
                form.financing.status = 'Disetujui'
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
                form.post('/admin/financings/draft', {
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
        searchSupplierQuery,
        supplierResults,
        isLoadingSearchSupplier,
        selectedSupplier,
        isSupplierSelected,
        // Methods
        resetSupplierSelection,
        resetAnggotaSelection,
        selectAnggota,
        selectSupplier,
        addHeir,
        removeHeir,
        submit,
        saveDraft,
        finalize
    }
}
