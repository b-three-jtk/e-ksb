import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import { useForm, router } from '@inertiajs/vue3'

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
            nama: initialData?.anggota?.nama || '',
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

            status_pekerjaan: initialData?.anggota?.status_pekerjaan || '',
            jabatan_pekerjaan: initialData?.anggota?.jabatan_pekerjaan || '',
            nama_perusahaan: initialData?.anggota?.nama_perusahaan || '',
            bidang_usaha: initialData?.anggota?.bidang_usaha || '',
            lama_bekerja: initialData?.anggota?.lama_bekerja,
            alamat_tempat_bekerja: initialData?.anggota?.alamat_tempat_bekerja || '',
            no_telp_kantor: initialData?.anggota?.no_telp_kantor || '',

            jml_gaji_pokok: initialData?.anggota?.jml_gaji_pokok || '',
            jml_penghasilan_usaha: initialData?.anggota?.jml_penghasilan_usaha || '',
            jml_penghasilan_pasangan: initialData?.anggota?.jml_penghasilan_pasangan || '',
            jml_penghasilan_lainnya: initialData?.anggota?.jml_penghasilan_lainnya || '',
            jml_biaya_hidup_keluarga: initialData?.anggota?.jml_biaya_hidup_keluarga || '',
            jml_biaya_pendidikan: initialData?.anggota?.jml_biaya_pendidikan || '',
            jml_cicilan: initialData?.anggota?.jml_cicilan || '',
            jml_biaya_lainnya: initialData?.anggota?.jml_biaya_lainnya || '',

            is_have_eligible_saving: initialData?.anggota?.is_have_eligible_saving || null,
            is_have_no_obligation: initialData?.anggota?.is_have_no_obligation || null,
            ahli_waris: initialData?.anggota?.ahli_waris || [],
        },
        // Pembiayaan data
        pembiayaan: {
            id: initialData?.pembiayaan?.id || null,
            nama_barang: initialData?.pembiayaan?.nama_barang || '',
            jenis_barang_id: initialData?.pembiayaan?.jenis_barang_id || null,
            brand: initialData?.pembiayaan?.brand || '',
            kondisi_produk: initialData?.pembiayaan?.kondisi_produk || '',
            kuantitas: initialData?.pembiayaan?.kuantitas || null,
            spesifikasi_barang: initialData?.pembiayaan?.spesifikasi_barang || '',
            harga_beli_per_unit: initialData?.pembiayaan?.harga_beli_per_unit || '',
            harga_perolehan: initialData?.pembiayaan?.harga_perolehan || null,
            margin_keuntungan: initialData?.pembiayaan?.margin_keuntungan || null,
            akad_wakalah_date: initialData?.pembiayaan?.akad_wakalah_date || null,
            metode_pembayaran: initialData?.pembiayaan?.metode_pembayaran || '',
            tgl_akad: initialData?.pembiayaan?.tgl_akad || '',
            uang_muka: initialData?.pembiayaan?.uang_muka || null,
            status: initialData?.pembiayaan?.status || 'Menunggu Kelengkapan Dokumen',
            struk_pembelian: initialData?.pembiayaan?.struk_pembelian || null,
            tenor: initialData?.pembiayaan?.tenor || null,
            satuan_tenor: initialData?.pembiayaan?.satuan_tenor || 'Bulan',
            harga_perkiraan: initialData?.pembiayaan?.harga_perkiraan || null,
            pemasok_id: initialData?.pembiayaan?.pemasok_id || null,
            tangguh_tgl_pembayaran: initialData?.pembiayaan?.tangguh_tgl_pembayaran || null,
        },
        jaminan: {
            jenis_jaminan: initialData?.jaminan?.jenis_jaminan || '',
            nama_pemilik: initialData?.jaminan?.nama_pemilik || '',
            nilai_perkiraan_pasar: initialData?.jaminan?.nilai_perkiraan_pasar || 0,
            lokasi_kondisi_jaminan: initialData?.jaminan?.lokasi_kondisi_jaminan || '',
        },
        verification: initialData?.verification || [],
        documents: {
            family_card: initialData?.documents?.family_card || null,
            income_slip: initialData?.documents?.income_slip || null,
            bank_book: initialData?.documents?.bank_book || null,
            struk_pembelian: initialData?.documents?.struk_pembelian || null,
            akad_document: initialData?.documents?.akad_document || null,
            akad_wakalah_document: initialData?.documents?.akad_wakalah_document || null
        },
        // Pemasok data
        pemasok: {
            nama_pemasok: initialData?.pemasok?.nama_pemasok || '',
            alamat_pemasok: initialData?.pemasok?.alamat_pemasok || '',
            kontak_pemasok: initialData?.pemasok?.kontak_pemasok || '',
        },
        is_wakalah: initialData?.pembiayaan?.is_wakalah || false,
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

        form.anggota.status_pekerjaan = anggota.pekerjaan_anggota?.status_pekerjaan || ''
        form.anggota.jabatan_pekerjaan = anggota.pekerjaan_anggota?.jabatan_pekerjaan || ''
        form.anggota.nama_perusahaan = anggota.pekerjaan_anggota?.nama_perusahaan || ''
        form.anggota.bidang_usaha = anggota.pekerjaan_anggota?.bidang_usaha || ''
        form.anggota.lama_bekerja = anggota.pekerjaan_anggota?.lama_bekerja || 0
        form.anggota.alamat_tempat_bekerja = anggota.pekerjaan_anggota?.alamat_tempat_bekerja || ''
        form.anggota.no_telp_kantor = anggota.pekerjaan_anggota?.no_telp_kantor || ''

        form.anggota.jml_gaji_pokok = anggota.keuangan_anggota?.jml_gaji_pokok || ''
        form.anggota.jml_penghasilan_usaha = anggota.keuangan_anggota?.jml_penghasilan_usaha || ''
        form.anggota.jml_penghasilan_pasangan = anggota.keuangan_anggota?.jml_penghasilan_pasangan || ''
        form.anggota.jml_penghasilan_lainnya = anggota.keuangan_anggota?.jml_penghasilan_lainnya || ''
        form.anggota.jml_biaya_hidup_keluarga = anggota.keuangan_anggota?.jml_biaya_hidup_keluarga || ''
        form.anggota.jml_biaya_pendidikan = anggota.keuangan_anggota?.jml_biaya_pendidikan || ''
        form.anggota.jml_cicilan = anggota.keuangan_anggota?.jml_cicilan || ''
        form.anggota.jml_biaya_lainnya = anggota.keuangan_anggota?.jml_biaya_lainnya || ''

        form.anggota.is_have_eligible_saving = anggota.is_have_eligible_saving || false
        form.anggota.is_have_no_obligation = anggota.is_have_no_obligation || false

        form.documents.family_card = anggota.family_card || null,
        form.documents.income_slip = anggota.income_slip || null,
        form.documents.bank_book = anggota.bank_book || null,

        form.anggota.ahli_waris = anggota.ahli_waris || []

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

            status_pekerjaan: '',
            jabatan_pekerjaan: '',
            nama_perusahaan: '',
            bidang_usaha: '',
            lama_bekerja: null,
            alamat_tempat_bekerja: '',
            no_telp_kantor: '',

            jml_gaji_pokok: '',
            jml_penghasilan_usaha: '',
            jml_penghasilan_pasangan: '',
            jml_penghasilan_lainnya: '',
            jml_biaya_hidup_keluarga: '',
            jml_biaya_pendidikan: '',
            jml_cicilan: '',
            jml_biaya_lainnya: '',

            is_have_eligible_saving: null,
            is_have_no_obligation: null,

            ahli_waris: [],
        }
        form.pembiayaan = {
            id: null,
            nama_barang: '',
            jenis_barang_id: null,
            brand: '',
            kondisi_produk: '',
            kuantitas: null,
            spesifikasi_barang: '',
            harga_beli_per_unit: '',
            harga_perolehan: null,
            margin_keuntungan: null,
            akad_wakalah_date: null,
            metode_pembayaran: '',
            tgl_akad: '',
            uang_muka: null,
            status: 'Menunggu Kelengkapan Dokumen',
            struk_pembelian: null,
            tenor: null,
            satuan_tenor: 'Bulan',
            harga_perkiraan: null,
            pemasok_id: null,
            tangguh_tgl_pembayaran: null,
        }
        form.jaminan = {
            jenis_jaminan: '',
            nama_pemilik: '',
            nilai_perkiraan_pasar: null,
            lokasi_kondisi_jaminan: '',
        }
        form.pemasok = {
            nama_pemasok: '',
            alamat_pemasok: '',
            kontak_pemasok: '',
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
        form.pemasok.kontak_pemasok = pemasok.kontak_pemasok || ''

        pemasokResults.value = []
        isPemasokSelected.value = true
    }

    const resetPemasokSelection = () => {
        selectedPemasok.value = null
        searchPemasokQuery.value = ''
        form.pemasok = {
            nama_pemasok: '',
            alamat_pemasok: '',
            kontak_pemasok: '',
        }
        isPemasokSelected.value = false
    }

    // AhliWariss
    const addAhliWaris = (heirData) => {
        if (!heirData.nik_ahli_waris || !heirData.nama_ahli_waris || !heirData.hubungan || !heirData.kontak_ahli_waris) {
            toast('Lengkapi semua field untuk menambahkan ahli waris!', {
                type: 'error',
                position: 'bottom-right',
            })
            return
        }

        form.anggota.ahli_waris.push({
            nik_ahli_waris: heirData.nik_ahli_waris,
            nama_ahli_waris: heirData.nama_ahli_waris,
            hubungan: heirData.hubungan,
            kontak_ahli_waris: heirData.kontak_ahli_waris,
        })
    }

    const removeAhliWaris = (index) => {
        form.anggota.ahli_waris.splice(index, 1)
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

    const batalkan = () => {
    Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin membatalkan permohonan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, batal',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#009141',
    }).then((result) => {
            if (result.isConfirmed) {
                if (form.pembiayaan.id) {
                    form.delete(`/admin/pembiayaan/batal/${form.pembiayaan.id}`, {
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            toast(page.props.flash.success, {
                                type: 'success',
                                position: 'bottom-right',
                            })
                        }
                    },
                    onError: (errors) => {
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
                    router.visit('/admin/pembiayaan')
                    toast('Berhasil membatalkan permohonan', {
                        type: 'success',
                        position:'bottom-right'
                    })
                }
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
        addAhliWaris,
        removeAhliWaris,
        submit,
        saveDraft,
        finalize,
        batalkan
    }
}
