import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import { useForm } from '@inertiajs/vue3'

export function useFinancingForm(initialData = null) {
    // State
    const searchQuery = ref('')
    const memberResults = ref([])
    const isLoadingSearch = ref(false)
    const selectedMember = ref(null)
    const isMemberSelected = ref(false)

    const searchSupplierQuery = ref('')
    const supplierResults = ref([])
    const isLoadingSearchSupplier = ref(false)
    const selectedSupplier = ref(null)
    const isSupplierSelected = ref(false)

    const form = useForm({
        // Member data
        member: {
            user_code: initialData?.member?.user_code || '',
            name: initialData?.member?.name || '',
            nik: initialData?.member?.nik || '',
            email: initialData?.member?.email || '',
            phone_number: initialData?.member?.phone_number || '',
            gender: initialData?.member?.gender || '',
            birth_place: initialData?.member?.birth_place || '',
            birth_date: initialData?.member?.birth_date || '',
            last_education: initialData?.member?.last_education || '',
            domicile_address: initialData?.member?.domicile_address || '',
            residential_address: initialData?.member?.residential_address || '',
            marital_status: initialData?.member?.marital_status || '',
            dependents: initialData?.member?.dependents || 0,

            employment_status: initialData?.member?.employment_status || '',
            job_title: initialData?.member?.job_title || '',
            company_or_business_name: initialData?.member?.company_or_business_name || '',
            business_field: initialData?.member?.business_field || '',
            tenure_year: initialData?.member?.tenure_year || 0,
            workplace_address: initialData?.member?.workplace_address || '',
            workplace_contact: initialData?.member?.workplace_contact || '',

            gaji_pokok_amount: initialData?.member?.gaji_pokok_amount || '',
            penghasilan_usaha_amount: initialData?.member?.penghasilan_usaha_amount || '',
            penghasilan_pasangan_amount: initialData?.member?.penghasilan_pasangan_amount || '',
            penghasilan_lainnya_amount: initialData?.member?.penghasilan_lainnya_amount || '',
            biaya_hidup_keluarga_amount: initialData?.member?.biaya_hidup_keluarga_amount || '',
            biaya_pendidikan_amount: initialData?.member?.biaya_pendidikan_amount || '',
            jumlah_cicilan_amount: initialData?.member?.jumlah_cicilan_amount || '',
            jumlah_biaya_lainnya_amount: initialData?.member?.jumlah_biaya_lainnya_amount || '',

            is_have_eligible_saving: initialData?.member?.is_have_eligible_saving || null,
            is_have_no_obligation: initialData?.member?.is_have_no_obligation || null,
            heirs: initialData?.member?.heirs || [],
        },
        // Financing data
        financing: {
            name: initialData?.financing?.name || '',
            product_type_id: initialData?.financing?.product_type_id || null,
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


    // Search members
    let searchTimeout = null

    watch(() => searchQuery.value, (query) => {
        // 1. Bersihkan timer sebelumnya setiap kali user mengetik karakter baru
        if (searchTimeout) {
            clearTimeout(searchTimeout)
        }

        if (!query || query.length < 2) {
            memberResults.value = []
            return
        }

        // 2. Buat timer baru
        searchTimeout = setTimeout(async () => {
            isLoadingSearch.value = true
            try {
                const response = await axios.get('/admin/members/search', {
                    params: { q: query }
                })
                memberResults.value = response.data.members
            } catch (error) {
                console.error('Error searching members:', error)
                memberResults.value = []
            } finally {
                isLoadingSearch.value = false
            }
        }, 500) // 500ms delay
    })

    // Pilih member
    const selectMember = (member) => {
        selectedMember.value = member
        searchQuery.value = member.name

        console.log('member:',member);

        // Update member form
        form.member.user_code = member.user.user_code || ''
        form.member.name = member.user.name || ''
        form.member.nik = member.user.nik || ''
        form.member.email = member.user.email || ''
        form.member.phone_number = member.user.phone_number || ''
        form.member.gender = member.gender || ''
        form.member.birth_place = member.birth_place || ''
        form.member.birth_date = member.birth_date || ''
        form.member.last_education = member.last_education || ''
        form.member.domicile_address = member.domicile_address || ''
        form.member.residential_address = member.residential_address || ''
        form.member.marital_status = member.marital_status || ''
        form.member.dependents = member.dependents || 0

        form.member.employment_status = member.member_jobs?.employment_status || ''
        form.member.job_title = member.member_jobs?.job_title || ''
        form.member.company_or_business_name = member.member_jobs?.company_or_business_name || ''
        form.member.business_field = member.member_jobs?.business_field || ''
        form.member.tenure_year = member.member_jobs?.tenure_year || 0
        form.member.workplace_address = member.member_jobs?.workplace_address || ''
        form.member.workplace_contact = member.member_jobs?.workplace_contact || ''

        form.member.gaji_pokok_amount = member.financials?.gaji_pokok_amount || ''
        form.member.penghasilan_usaha_amount = member.financials?.penghasilan_usaha_amount || ''
        form.member.penghasilan_pasangan_amount = member.financials?.penghasilan_pasangan_amount || ''
        form.member.penghasilan_lainnya_amount = member.financials?.penghasilan_lainnya_amount || ''
        form.member.biaya_hidup_keluarga_amount = member.financials?.biaya_hidup_keluarga_amount || ''
        form.member.biaya_pendidikan_amount = member.financials?.biaya_pendidikan_amount || ''
        form.member.jumlah_cicilan_amount = member.financials?.jumlah_cicilan_amount || ''
        form.member.jumlah_biaya_lainnya_amount = member.financials?.jumlah_biaya_lainnya_amount || ''

        form.member.is_have_eligible_saving = member.is_have_eligible_saving || false
        form.member.is_have_no_obligation = member.is_have_no_obligation || false

        form.documents.family_card = member.family_card || null,
        form.documents.income_slip = member.income_slip || null,
        form.documents.bank_book = member.bank_book || null,

        form.member.heirs = member.heirs || []

        memberResults.value = []
        isMemberSelected.value = true
    }

    const resetMemberSelection = () => {
        selectedMember.value = null
        searchQuery.value = ''
        form.member = {
            user_code: '',
            name: '',
            nik: '',
            email: '',
            phone_number: '',
            gender: '',
            birth_place: '',
            birth_date: '',
            last_education: '',
            domicile_address: '',
            residential_address: '',
            marital_status: '',
            dependents: null,

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
            product_type_id: null,
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
        isMemberSelected.value = false
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

        form.member.heirs.push({
            heir_nik: heirData.heir_nik,
            heir_name: heirData.heir_name,
            relationship: heirData.relationship,
            heir_contact: heirData.heir_contact,
        })
    }

    const removeHeir = (index) => {
        form.member.heirs.splice(index, 1)
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
    if (initialData?.member) {
            isMemberSelected.value = true
            selectedMember.value = initialData.member
            searchQuery.value = initialData.member.name
        }
    })

    return {
        // State
        form,
        searchQuery,
        memberResults,
        isLoadingSearch,
        selectedMember,
        isMemberSelected,
        searchSupplierQuery,
        supplierResults,
        isLoadingSearchSupplier,
        selectedSupplier,
        isSupplierSelected,
        // Methods
        resetSupplierSelection,
        resetMemberSelection,
        selectMember,
        selectSupplier,
        addHeir,
        removeHeir,
        submit,
        saveDraft,
        finalize
    }
}
