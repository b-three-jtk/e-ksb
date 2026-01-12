<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'

const props = defineProps({
	member: {
		type: Object,
		default: () => ({
			id: '',
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

const setDecision = (value) => {
	decision.value = value
}

const handleContinue = () => {
    if (!decision.value) {
        alert('Silakan pilih keputusan terlebih dahulu')
        return
    }

    router.post(
        `/admin/verifikasi/${props.member.id}/approval`,
        {
            decision: decision.value,
            note: decision.value === 'rejected' ? note.value : '',
        },
        {
            onSuccess: () => {
                window.location.href = '/admin/verifikasi'
            },
        }
    )
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
				<section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100 xl:col-span-2">
					<div
						class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
						<h2 class="font-heading text-xl font-semibold text-gray-900 dark:text-white">
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
									@click="() => { decision = 'approved'; handleContinue(); }"
									:class="[
										'h-11 min-w-[120px] rounded-lg px-5 text-sm font-semibold transition',
										'bg-blue-800 text-white hover:bg-blue-900'
									]"
								>
									Diterima
								</button>
								<button
									type="button"
									@click="setDecision('rejected')"
									:class="[
										'h-11 min-w-[120px] rounded-lg px-5 text-sm font-semibold transition',
										decision === 'rejected'
											? 'bg-red-700 text-white shadow'
											: 'bg-red-700 text-white hover:bg-red-900'
									]"
								>
									Ditolak
								</button>
							</div>
							<div v-if="decision === 'rejected'" class="flex flex-col gap-2 md:col-span-2">
								<label class="text-sm font-medium text-gray-600 mt-4">Catatan</label>
								<textarea
									v-model="note"
									rows="3"
									placeholder="Catatan jika ditolak"
									class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
								></textarea>
							</div>
						</div>
					</div>

					<div v-if="decision === 'rejected'" class="mt-6 flex justify-end">
						<button
							type="button"
							@click="handleContinue"
							class="inline-flex items-center justify-center rounded-lg bg-blue-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900"
						>
							Lanjutkan
						</button>
					</div>
				</section>

				<div class="flex flex-col gap-4">
					<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
						<h3 class="mb-4 text-base font-semibold text-gray-900">Foto Calon Anggota</h3>
						<div class="mx-auto flex w-60 aspect-square items-center justify-center rounded-lg border-2 bg-gray-50">
							<img v-if="member.photo_url" :src="member.photo_url" alt="Foto calon anggota" class="h-full w-full rounded-lg object-cover" />
						</div>
					</section>

					<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
						<h3 class="mb-4 text-base font-semibold text-gray-900">Foto KTP</h3>
						<div class="flex w-full aspect-[16/10] items-center justify-center rounded-lg border-2 bg-gray-50">
							<img v-if="member.id_card_url" :src="member.id_card_url" alt="Foto KTP" class="h-full w-full rounded-lg object-cover" />
						</div>
					</section>
				</div>
			</div>
		</div>
	</AdminLayout>
</template>
