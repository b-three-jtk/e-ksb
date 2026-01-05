<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'

const props = defineProps({
	member: {
		type: Object,
		default: () => ({
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
	// TODO: integrate submit/approval logic
}
</script>

<template>
	<AdminLayout>
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
				<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100 xl:col-span-2">
					<div class="pb-4">
						<h2 class="text-lg font-semibold text-gray-900">Detail Data Calon Anggota</h2>
						<div class="mt-3 h-px bg-gray-200"></div>
					</div>

					<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
						<div class="flex flex-col gap-2">
							<label class="text-sm font-medium text-gray-600">Nama Lengkap</label>
							<input
								:value="member.name"
								disabled
								class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
							/>
						</div>
						<div class="flex flex-col gap-2">
							<label class="text-sm font-medium text-gray-600">NIK</label>
							<input
								:value="member.nik"
								disabled
								class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
							/>
						</div>
						<div class="flex flex-col gap-2">
							<label class="text-sm font-medium text-gray-600">Unit Kerja</label>
							<input
								:value="member.work_unit"
								disabled
								class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
							/>
						</div>
						<div class="flex flex-col gap-2">
							<label class="text-sm font-medium text-gray-600">Nama Lembaga</label>
							<input
								:value="member.institution"
								disabled
								class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
							/>
						</div>
						<div class="flex flex-col gap-2">
							<label class="text-sm font-medium text-gray-600">Email</label>
							<input
								:value="member.email"
								disabled
								class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
							/>
						</div>
						<div class="flex items-end justify-start gap-3 md:justify-end">
							<button
								type="button"
								@click="setDecision('approved')"
								:class="[
									'h-11 min-w-[120px] rounded-lg px-5 text-sm font-semibold transition',
									decision === 'approved'
										? 'bg-blue-800 text-white shadow'
										: 'bg-blue-800 text-white hover:bg-blue-900'
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
							<label class="text-sm font-medium text-gray-600">Catatan</label>
							<textarea
								v-model="note"
								rows="3"
								placeholder="Catatan jika ditolak"
								class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300"
							></textarea>
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
						<div class="flex h-56 items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50">
							<template v-if="member.photo_url">
								<img :src="member.photo_url" alt="Foto calon anggota" class="h-full w-full rounded-lg object-cover" />
							</template>
							<template v-else>
								<div class="text-center text-gray-400">
									<svg class="mx-auto mb-3 h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
										<rect x="3" y="5" width="18" height="14" rx="2" ry="2" />
										<circle cx="8.5" cy="11.5" r="1.5" />
										<path d="m21 15-3.5-3.5a2 2 0 0 0-3 0L9 17" />
									</svg>
									<p class="text-sm">Belum ada foto diunggah</p>
								</div>
							</template>
						</div>
					</section>

					<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
						<h3 class="mb-4 text-base font-semibold text-gray-900">Foto KTP</h3>
						<div class="flex h-48 items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50">
							<template v-if="member.id_card_url">
								<img :src="member.id_card_url" alt="Foto KTP" class="h-full w-full rounded-lg object-cover" />
							</template>
							<template v-else>
								<div class="text-center text-gray-400">
									<svg class="mx-auto mb-3 h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
										<rect x="3" y="7" width="18" height="10" rx="2" ry="2" />
										<path d="M7 11h6" />
										<path d="M7 13h4" />
									</svg>
									<p class="text-sm">Belum ada foto KTP</p>
								</div>
							</template>
						</div>
					</section>
				</div>
			</div>
		</div>
	</AdminLayout>
</template>
