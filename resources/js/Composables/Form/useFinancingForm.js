import { ref, watch, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import { useForm } from '@inertiajs/vue3'

export function useFinancingForm() {
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
        member: {
            member_number: '',
            name: '',
            nik: '',
            gender: '',
            birth_place: '',
            birth_date: '',
            last_education: '',
            address: '',
            residential_address: '',
            email: '',
            phone_number: '',
            marital_status: '',
            dependents: '',
            heirs: [],
            incomes: [],
            expenses: [],
            income_slip: null,
            family_card: null,
            bank_book: null,
        },
        income_type: '',
        income_amount: '',
        expense_type: '',
        expense_amount: '',
        financing: {
            product_name: '',
            product_type: '',
            brand: '',
            color: '',
            condition: '',
            qty: '',
            description: '',
            down_payment: '',
            down_payment_proof: null,
            procurement_proof: null,
            akad_document: null,
        },
        supplier: {
            name: '',
            contact: '',
            address: '',
            link_address: '',
        },
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
            memberResults.value = response.data
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

        form.member.member_number = member.member_number
        form.member.name = member.name
        form.member.nik = member.nik
        form.member.email = member.email
        form.member.phone_number = member.phone_number
        form.member.gender = member.gender || ''
        form.member.birth_place = member.birth_place || ''
        form.member.birth_date = member.birth_date || ''
        form.member.last_education = member.last_education || ''
        form.member.address = member.address || ''
        form.member.residential_address = member.residential_address || ''
        form.member.marital_status = member.marital_status || ''
        form.member.dependents = member.dependents || ''
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
            m.member_number.includes(searchQuery.value)
        )
    })

    const resetMemberSelection = () => {
        selectedMember.value = null
        searchQuery.value = ''
        form.member = {
            member_number: '',
            name: '',
            nik: '',
            email: '',
            phone_number: '',
            gender: '',
            birth_place: '',
            birth_date: '',
            last_education: '',
            address: '',
            residential_address: '',
            marital_status: '',
            dependents: '',
            heirs: [],
            incomes: [],
            expenses: [],
            income_slip: null,
            family_card: null,
            bank_book: null,
        }
        isMemberSelected.value = false
    }

    // search supplier
    watch(() => searchSupplierQuery.value, async (query) => {
        if (!query || query.length < 2) {
            supplierResults.value = []
            return
        }

        isLoadingSearchSupplier.value = true
        try {
            const response = await axios.get('/admin/suppliers/search', {
                params: { q: query }
            })
            supplierResults.value = response.data
        } catch (error) {
            console.error('Error searching suppliers:', error)
            supplierResults.value = []
        } finally {
            isLoadingSearchSupplier.value = false
        }
    })

    // Pilih member
    const selectSupplier = (supplier) => {
        selectedSupplier.value = supplier
        searchSupplierQuery.value = supplier.name

        form.supplier.name = supplier.name
        form.supplier.contact = supplier.contact
        form.supplier.address = supplier.address || ''
        form.supplier.link_address = supplier.link_address || ''

        supplierResults.value = []
        isSupplierSelected.value = true
    }

    // Filter members
    const filteredSuppliers = computed(() => {
        return supplierResults.value.filter(m =>
            m.name.toLowerCase().includes(searchSupplierQuery.value.toLowerCase()) ||
            m.contact.includes(searchSupplierQuery.value)
        )
    })

    // Income & Expense
    const addIncome = () => {
        if (!form.income_type || !form.income_amount) {
            alert('Isi jenis dan jumlah penghasilan!')
            return
        }

        const existingIncome = form.incomes.find(i => i.financial_type === form.income_type)
        if (existingIncome) {
            existingIncome.amount = form.income_amount
        } else {
            form.incomes.push({
                financial_type: form.income_type,
                amount: form.income_amount
            })
        }

        form.income_type = ''
        form.income_amount = ''
    }

    const removeIncome = (index) => {
        form.incomes.splice(index, 1)
    }

    const addExpense = () => {
        if (!form.expense_type || !form.expense_amount) {
            alert('Isi jenis dan jumlah pengeluaran!')
            return
        }

        const existingExpense = form.expenses.find(e => e.financial_type === form.expense_type)
        if (existingExpense) {
            existingExpense.amount = form.expense_amount
        } else {
            form.expenses.push({
                financial_type: form.expense_type,
                amount: form.expense_amount
            })
        }

        form.expense_type = ''
        form.expense_amount = ''
    }

    const removeExpense = (index) => {
        form.expenses.splice(index, 1)
    }

    // Heirs
    const addHeir = (heirData) => {
        if (!heirData.name || !heirData.relationship || !heirData.contact) {
            alert('Lengkapi semua field untuk menambahkan ahli waris!')
            return
        }

        form.member.heirs.push({
            nik: heirData.nik,
            name: heirData.name,
            relationship: heirData.relationship,
            contact: heirData.contact,
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

    const submitDraft = () => {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin menyimpan draft ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#007943',
        }).then((result) => {
            if (result.isConfirmed) {
                form.post('/admin/financing/store/draft', {
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            toast(page.props.flash.success, {
                                type: 'success',
                                position: 'bottom-right',
                            })
                        }
                    },
                    onError: (errors) => {
                        console.log('Validation errors:', errors)

                        // Show all errors
                        const errorMessages = Object.values(errors).flat()

                        if (errorMessages.length > 0) {
                            toast(errorMessages.join(', '), {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        } else {
                            toast('Gagal menyimpan draft', {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        }
                    }
                })
            }
        })
    }

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
        resetMemberSelection,
        selectMember,
        selectSupplier,
        addIncome,
        removeIncome,
        addExpense,
        removeExpense,
        addHeir,
        removeHeir,
        submitDraft,
    }
}
