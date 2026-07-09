<script setup>
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import axios from 'axios'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    anggotaId: {
        type: [String, Number],
        required: true
    }
})

const emit = defineEmits(['close', 'created'])

const newRekeningNo = ref('')
const newRekeningBank = ref('')
const newRekeningName = ref('')
const isCreatingRekening = ref(false)

const createNewRekening = async () => {
    if (!newRekeningNo.value.trim() || !newRekeningBank.value.trim() || !newRekeningName.value.trim()) return
    isCreatingRekening.value = true
    try {
        const response = await axios.post('/admin/pembiayaan/rekening-anggota', {
            no_rekening: newRekeningNo.value,
            nama_bank: newRekeningBank.value,
            atas_nama: newRekeningName.value,
            anggota_id: props.anggotaId,
        })

        // Reset state
        newRekeningNo.value = ''
        newRekeningBank.value = ''
        newRekeningName.value = ''

        emit('created', response.data)
        emit('close')
    } catch (error) {
        console.error('Error creating rekening:', error)
        toast(error.response?.data?.message || 'Gagal membuat rekening', {
            type: 'error',
            position: 'bottom-right',
        })
    } finally {
        isCreatingRekening.value = false
    }
}

const closeModal = () => {
    newRekeningNo.value = ''
    newRekeningBank.value = ''
    newRekeningName.value = ''
    emit('close')
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-200 mb-4">Tambah Rekening Baru</h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Bank</label>
                    <input v-model="newRekeningBank" type="text" placeholder="Masukkan nama bank (cth: BCA, Mandiri)..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewRekening" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nomor Rekening</label>
                    <input v-model="newRekeningNo" type="text" placeholder="Masukkan nomor rekening..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewRekening" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Atas Nama (Pemilik Rekening)</label>
                    <input v-model="newRekeningName" type="text" placeholder="Masukkan nama pemilik rekening..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewRekening" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition font-medium">
                        Batal
                    </button>
                    <button @click="createNewRekening" :disabled="isCreatingRekening || !newRekeningNo.trim() || !newRekeningBank.trim() || !newRekeningName.trim()"
                        class="px-6 py-2 bg-primary hover:bg-secondary text-white rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed transition font-medium">
                        <span v-if="!isCreatingRekening">Simpan</span>
                        <span v-else class="flex items-center gap-2">
                            <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
