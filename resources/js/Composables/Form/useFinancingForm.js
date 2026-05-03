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
            spouse_name: initialData?.member?.spouse_name || '',
            dependents: initialData?.member?.dependents || 0,
            job_title: initialData?.member?.job_title || '',
            company_or_business_name: initialData?.member?.company_or_business_name || '',
            business_field: initialData?.member?.business_field || '',
            tenure_year: initialData?.member?.tenure_year || 0,
            workplace_address: initialData?.member?.workplace_address || '',
            workplace_contact: initialData?.member?.workplace_contact || '',
            heirs: initialData?.member?.heirs || [],
            incomes: initialData?.member?.incomes || [],
            expenses: initialData?.member?.expenses || [],
        },
        // Financing data
        financing: {
            name: initialData?.financing?.name || '',
            product_type_id: initialData?.financing?.product_type_id || null,
            brand: initialData?.financing?.brand || '',
            condition: initialData?.financing?.condition || '',
            qty: initialData?.financing?.qty || null,
            request_description: initialData?.financing?.request_description || '',
            cost_price: initialData?.financing?.cost_price || null,
            margin_amount: initialData?.financing?.margin_amount || null,
            is_wakalah: initialData?.financing?.is_wakalah || false,
            payment_method: initialData?.financing?.payment_method || '',
            akad_date: initialData?.financing?.akad_date || '',
            down_payment: initialData?.financing?.down_payment || null,
            notes: initialData?.financing?.notes || '',
            financing_status: initialData?.financing?.financing_status || 'Menunggu Kelengkapan Dokumen',
            purchase_receipt: initialData?.financing?.purchase_receipt || null
        },
        collateral: {
            collateral_type: initialData?.collateral?.collateral_type || '',
            owner_name: initialData?.collateral?.owner_name || '',
            estimated_market_value: initialData?.collateral?.estimated_market_value || 0,
            collateral_location: initialData?.collateral?.collateral_location || '',
        },
        documents: {
            family_card: initialData?.documents?.family_card || null,
            income_slip: initialData?.documents?.income_slip || null,
            bank_book: initialData?.documents?.bank_book || null,
            purchase_receipt: initialData?.documents?.purchase_receipt || null,
            akad_document: initialData?.documents?.akad_document || null,
        },
        // Supplier data
        supplier: {
            supplier_name: initialData?.supplier?.supplier_name || '',
            contact: initialData?.supplier?.contact || '',
            address: initialData?.supplier?.address || '',
            website_url: initialData?.supplier?.website_url || '',
        },
        // Local state untuk temporary input
        tenor: null,
        monthly_installment: null,
        monthly_income: null,
        income_type: '',
        income_amount: '',
        expense_type: '',
        expense_amount: '',
        family_card_file: null,
        income_slip_file: null,
        bank_book_file: null,
        purchase_receipt_file: null,
        akad_document_file: null,
        akad_wakalah_file: null
    })


    // Search members
    watch(() => searchQuery.value, async (query) => {
        if (!query || query.length < 2) {
            memberResults.value = []
            return
        }

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
    })

    // Pilih member
    const selectMember = (member) => {
        selectedMember.value = member
        searchQuery.value = member.name

        // Update member form
        form.member.user_code = member.user_code || ''
        form.member.name = member.name || ''
        form.member.nik = member.nik || ''
        form.member.email = member.email || ''
        form.member.phone_number = member.phone_number || ''
        form.member.gender = member.gender || ''
        form.member.birth_place = member.birth_place || ''
        form.member.birth_date = member.birth_date || ''
        form.member.last_education = member.last_education || ''
        form.member.domicile_address = member.domicile_address || ''
        form.member.residential_address = member.residential_address || ''
        form.member.marital_status = member.marital_status || ''
        form.member.spouse_name = member.spouse_name || ''
        form.member.dependents = member.dependents || 0
        form.member.job_title = member.job_title || ''
        form.member.company_or_business_name = member.company_or_business_name || ''
        form.member.business_field = member.business_field || ''
        form.member.tenure_year = member.tenure_year || 0
        form.member.workplace_address = member.workplace_address || ''
        form.member.workplace_contact = member.workplace_contact || ''
        form.member.incomes = member.incomes || []
        form.member.expenses = member.expenses || []
        form.member.heirs = member.heirs || []

        memberResults.value = []
        isMemberSelected.value = true
    }

    // Filter members
    const filteredMembers = computed(() => {
        return memberResults.value.filter(m =>
            m.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            m.user_code.includes(searchQuery.value)
        )
    })

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
            spouse_name: '',
            dependents: null,
            job_title: '',
            company_or_business_name: '',
            business_field: '',
            tenure_year: null,
            workplace_address: '',
            workplace_contact: '',
            heirs: [],
            incomes: [],
            expenses: [],
        }
        form.financing = {
            name: '',
            product_type_id: null,
            brand: '',
            condition: '',
            qty: null,
            request_description: '',
            cost_price: null,
            margin_amount: null,
            is_wakalah: false,
            payment_method: '',
            akad_date: '',
            down_payment: null,
            notes: '',
            financing_status: ''
        }
        form.collateral = {
            collateral_type: '',
            owner_name: '',
            estimated_market_value: null,
            collateral_location: '',
        }
        form.supplier = {
            supplier_name: '',
            contact: '',
            address: '',
            website_url: '',
        }
        isMemberSelected.value = false
    }

    // search supplier
    watch(() => searchSupplierQuery.value, async (query) => {
        if (!query || query.length < 3) {
            supplierResults.value = []
            return
        }

        isLoadingSearchSupplier.value = true
        try {
            const response = await axios.get('/admin/suppliers/search', {
                params: { q: query }
            })
            supplierResults.value = response.data.suppliers
        } catch (error) {
            console.error('Error searching suppliers:', error)
            supplierResults.value = []
        } finally {
            isLoadingSearchSupplier.value = false
        }
    })

    // Pilih supplier
    const selectSupplier = (supplier) => {
        selectedSupplier.value = supplier
        searchSupplierQuery.value = supplier.supplier_name

        form.supplier.supplier_name = supplier.supplier_name || ''
        form.supplier.contact = supplier.contact || ''
        form.supplier.address = supplier.address || ''
        form.supplier.website_url = supplier.website_url || ''

        supplierResults.value = []
        isSupplierSelected.value = true
    }

    const filteredSuppliers = computed(() => {
        return Array.isArray(supplierResults.value) ? supplierResults.value : []
    })

    const resetSupplierSelection = () => {
        selectedSupplier.value = null
        searchSupplierQuery.value = ''
        form.supplier = {
            supplier_name: '',
            contact: '',
            address: '',
            website_url: '',
        }
        isSupplierSelected.value = false
    }

    // Income & Expense
    const addIncome = () => {
        if (!form.income_type || !form.income_amount) {
            alert('Isi jenis dan jumlah penghasilan!')
            return
        }

        const existingIncome = form.member.incomes.find(i => i.financial_type === form.income_type)
        if (existingIncome) {
            existingIncome.amount = form.income_amount
        } else {
            form.member.incomes.push({
                financial_type: form.income_type,
                amount: form.income_amount
            })
        }

        form.income_type = ''
        form.income_amount = ''
    }

    const removeIncome = (index) => {
        form.member.incomes.splice(index, 1)
    }

    const addExpense = () => {
        if (!form.expense_type || !form.expense_amount) {
            alert('Isi jenis dan jumlah pengeluaran!')
            return
        }

        const existingExpense = form.member.expenses.find(e => e.financial_type === form.expense_type)
        if (existingExpense) {
            existingExpense.amount = form.expense_amount
        } else {
            form.member.expenses.push({
                financial_type: form.expense_type,
                amount: form.expense_amount
            })
        }

        form.expense_type = ''
        form.expense_amount = ''
    }

    const removeExpense = (index) => {
        form.member.expenses.splice(index, 1)
    }

    // Heirs
    const addHeir = (heirData) => {
        if (!heirData.heir_nik || !heirData.heir_name || !heirData.relationship || !heirData.heir_contact) {
            alert('Lengkapi semua field untuk menambahkan ahli waris!')
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

    // Totals
    const totalIncome = computed(() => {
        return form.member.incomes.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
    })

    const totalExpense = computed(() => {
        return form.member.expenses.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
    })

    const netIncome = computed(() => {
        return totalIncome.value - totalExpense.value
    })

    const submit = (status) => {
        if ((form.financing.financing_status === 'Menunggu Kelengkapan Dokumen' || form.financing.financing_status === 'Ditolak') && status === 'PENDING_REVIEW') {
            form.financing.financing_status = 'Belum Ditinjau'
        }

        if (form.financing.financing_status === 'Disetujui' && status === 'FINAL') {
            if (form.financing.payment_method === 'Cicilan') {
                form.financing.financing_status = 'Angsuran Berjalan'
            } else {
                form.financing.financing_status = 'Lunas'
            }
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin menyimpan permohonan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#007943',
        }).then((result) => {
            if (result.isConfirmed) {
                form.post('/admin/financing/store', {
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
        filteredMembers,
        totalIncome,
        totalExpense,
        netIncome,
        searchSupplierQuery,
        supplierResults,
        isLoadingSearchSupplier,
        selectedSupplier,
        isSupplierSelected,
        filteredSuppliers,
        // Methods
        resetSupplierSelection,
        resetMemberSelection,
        selectMember,
        selectSupplier,
        addIncome,
        removeIncome,
        addExpense,
        removeExpense,
        addHeir,
        removeHeir,
        submit,
    }
}
