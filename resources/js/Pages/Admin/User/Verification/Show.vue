<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'

const props = defineProps({
	member: {
		type: Object,
		default: () => ({
			id: '',
			member_number: '',
			name: '',
			nik: '',
			work_unit: '',
			institution: '',
			email: '',
			note: '',
			photo_url: null,
			id_card_url: null,
		}),
	},
})

const note = ref(props.member.note ?? '')
const decision = ref(null)
const processing = ref(false)

const member = computed(() => ({
	id: props.member?.id ?? '',
	name: props.member?.name ?? '',
	nik: props.member?.nik ?? '',
	work_unit: props.member?.work_unit ?? '',
	institution: props.member?.institution ?? '',
	email: props.member?.email ?? '',
	photo_url: props.member?.photo_url ?? null,
	id_card_url: props.member?.id_card_url ?? null,
}))

const isNoteValid = computed(() => {
	if (decision.value === 'rejected') {
		return note.value.trim() !== ''
	}
	return true
})

const setDecision = (value) => {
	decision.value = value
}

const handleApproved = () => {
	Swal.fire({
		title: 'Terima Calon Anggota?',
		text: 'Apakah Anda yakin ingin menerima calon anggota ini?',
		icon: 'question',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Ya, Terima!',
		cancelButtonText: 'Batal'
	}).then((result) => {
		if (result.isConfirmed) {
			processing.value = true
			router.post(
				`/admin/verifikasi/${props.member.member_number}/approval`,
				{
					decision: 'approved',
					note: '',
				},
				{
					onSuccess: (response) => {
						processing.value = false
						toast.success('Pemberitahuan terkirim ke email anggota', {
							autoClose: 2000,
							position: 'bottom-right'
						})
						router.visit('/admin/users/verification')
					},
					onError: (error) => {
						processing.value = false
						const errorMessage = error?.message || 'Gagal mengirim pemberitahuan'
						toast.error(`${errorMessage}`, {
							autoClose: 3000,
							position: 'bottom-right'
						})
					}
				}
			)
		}
	})
}

const handleContinue = () => {
    if (!decision.value) {
        alert('Silakan pilih keputusan terlebih dahulu')
        return
    }

    Swal.fire({
        title: 'Tolak Calon Anggota?',
        text: 'Apakah Anda yakin ingin menolak calon anggota ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Tolak!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            processing.value = true
            router.post(
                `/admin/verifikasi/${props.member.member_number}/approval`,
                {
                    decision: decision.value,
                    note: decision.value === 'rejected' ? note.value : '',
                },
                {
                    onSuccess: (response) => {
                        processing.value = false
                        toast.success('Pemberitahuan terkirim ke email calon anggota', {
                            autoClose: 2000,
                            position: 'bottom-right'
                        })
                        router.visit('/admin/verifikasi')
                    },
                    onError: (error) => {
                        processing.value = false
                        const errorMessage = error?.message || 'Gagal mengirim pemberitahuan'
                        toast.error(`${errorMessage}`, {
                            autoClose: 3000,
                            position: 'bottom-right'
                        })
                    }
                }
            )
        }
    })
}
</script>

<template>
	<AdminLayout title="Verifikasi Calon Anggota">
		<div class="space-y-8">
			<div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
				<h1 class="text-2xl font-bold text-blue-900">Verifikasi Calon Anggota</h1>
				<nav class="flex items-center text-sm text-gray-500">
					<Link href="/admin/dashboard" class="hover:text-blue-600">Dashboard</Link>
					<span class="mx-2 text-gray-400">/</span>
					<Link href="/admin/verifikasi" class="hover:text-blue-700">Verifikasi</Link>
					<span class="mx-2 text-gray-400">/</span>
					<span class="font-semibold text-blue-700">Tinjauan</span>
				</nav>
			</div>

			<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
				<section class="rounded-xl bg-white dark:bg-gray-800 xl:col-span-2">
					<div
						class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
						<h2 class="font-head text-xl font-semibold text-gray-900 dark:text-white">
							Detail Data Calon Anggota
						</h2>
					</div>

					<div class="px-6 py-4 mt-4 gap-4">
						<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
						<ReadonlyField label="Nama Lengkap" :model-value="member.name" />
						<ReadonlyField label="NIK" :model-value="member.nik" />
						<ReadonlyField label="Unit Kerja" :model-value="member.work_unit" />
						<ReadonlyField label="Nama Lembaga" :model-value="member.institution" />
						<ReadonlyField label="Email" :model-value="member.email" type="email" />
							<div class="flex items-end justify-start gap-3 md:justify-end">
								<button
									type="button"
								@click="handleApproved"
									:disabled="processing"
									:class="[
										'h-11 min-w-30 rounded-lg px-5 text-sm font-semibold transition flex items-center justify-center gap-2',
										'bg-blue-800 text-white hover:bg-blue-900 disabled:opacity-50 disabled:cursor-not-allowed'
									]"
								>
									<span v-if="processing">
										<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
											<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
											<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
										</svg>
									</span>
									{{ processing ? 'Memproses...' : 'Diterima' }}
								</button>
								<button
									type="button"
									@click="setDecision('rejected')"
									:disabled="processing"
									:class="[
										'h-11 min-w-30 rounded-lg px-5 text-sm font-semibold transition',
										decision === 'rejected'
											? 'bg-red-700 text-white shadow'
											: 'bg-red-700 text-white hover:bg-red-900',
										'disabled:opacity-50 disabled:cursor-not-allowed'
									]"
								>
									Ditolak
								</button>
							</div>
							<div v-if="decision === 'rejected'" class="flex flex-col gap-2 md:col-span-2">
								<label class="text-sm font-medium text-gray-600 dark:text-white mt-4">
									Catatan
									<span class="text-error-500">*</span>
								</label>
								<textarea
									v-model="note"
									rows="3"
									placeholder="Catatan jika ditolak"
									class="w-full rounded-lg border border-gray-200 bg-white dark:bg-gray-800 px-4 py-3 text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
								></textarea>
							</div>
						</div>
					</div>

					<div v-if="decision === 'rejected'" class="p-8 mt-6 flex justify-end">
						<button
							type="button"
							@click="handleContinue"
							:disabled="!isNoteValid || processing"
							class="inline-flex items-center justify-center rounded-lg bg-blue-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900 disabled:opacity-50 disabled:cursor-not-allowed gap-2"
						>
							<span v-if="processing">
								<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
								</svg>
							</span>
							{{ processing ? 'Memproses...' : 'Lanjutkan' }}
						</button>
					</div>
				</section>

				<div class="flex flex-col gap-4">
					<section class="rounded-xl bg-white dark:bg-gray-800 p-6">
						<h3 class="mb-4 text-base font-semibold font-head text-gray-900 dark:text-white">Foto Calon Anggota</h3>
						<div class="mx-auto flex w-60 aspect-square items-center justify-center rounded-lg border-2 bg-gray-50 dark:border-gray-700">
							<img v-if="member.photo_url" :src="member.photo_url" alt="Foto calon anggota" class="h-full w-full rounded-lg object-cover" />
						</div>
					</section>

					<section class="rounded-xl bg-white dark:bg-gray-800 p-6">
						<h3 class="mb-4 text-base font-semibold font-head text-gray-900 dark:text-white">Foto KTP</h3>
						<div class="flex w-full aspect-16/10 items-center justify-center rounded-lg border-2 bg-gray-50 dark:border-gray-700">
							<img v-if="member.id_card_url" :src="member.id_card_url" alt="Foto KTP" class="h-full w-full rounded-lg object-cover" />
						</div>
					</section>
				</div>
			</div>
		</div>
	</AdminLayout>
</template>
