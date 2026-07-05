<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'

const props = defineProps({
	form: {
		type: Object,
		required: true,
	},
	errors: {
		type: Object,
		required: true,
	},
	getFieldError: {
		type: Function,
		required: true,
	},
	onlyLetters: {
		type: Function,
		required: true,
	},
	onlyNumbers: {
		type: Function,
		required: true,
	},
	hubunganOptions: {
		type: Array,
		default: () => [],
	},
})

const normalizePhoneNumber = (value, onlyNumbers) => {
	const digits = onlyNumbers(value)

	if (!digits) {
		return ''
	}

	if (digits.startsWith('0')) {
		return `62${digits.slice(1)}`
	}

	return digits.startsWith('62') ? digits : `62${digits}`
}
</script>

<template>
	<section class="p-6">
		<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-5">Ahli Waris</h3>

		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<BaseInputAdmin
				v-model="form.nik_ahli_waris"
				label="NIK Ahli Waris"
				type="text"
				placeholder="Isi dengan angka"
				required
                max="16"
				@input="form.nik_ahli_waris = onlyNumbers(form.nik_ahli_waris)"
				:error="getFieldError('nik_ahli_waris', errors.nik_ahli_waris)"
			/>

			<BaseInputAdmin
				v-model="form.nama_ahli_waris"
				label="Nama Ahli Waris"
				type="text"
				placeholder="Isi dengan huruf"
				required
				@input="form.nama_ahli_waris = onlyLetters(form.nama_ahli_waris)"
				:error="getFieldError('nama_ahli_waris', errors.nama_ahli_waris)"
			/>

			<BaseInputAdmin
				v-model="form.heir_hubungan"
				label="Hubungan Keluarga"
				type="select"
				required
				:selectables="hubunganOptions"
				:error="getFieldError('heir_hubungan', errors.heir_hubungan)"
			/>

			<BaseInputAdmin
				v-model="form.kontak_ahli_waris"
				label="Nomor Telepon Ahli Waris"
				type="text"
				placeholder="Contoh: 81234567890"
				required
				@input="form.kontak_ahli_waris = normalizePhoneNumber(form.kontak_ahli_waris, props.onlyNumbers)"
				:error="getFieldError('kontak_ahli_waris', errors.kontak_ahli_waris)"
			/>
		</div>
	</section>
</template>
