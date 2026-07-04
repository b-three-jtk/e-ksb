<script setup>
import { computed } from 'vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'

defineProps({
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
	genderOptions: {
		type: Array,
		default: () => [],
	},
	maritalStatusOptions: {
		type: Array,
		default: () => [],
	},
	educationOptions: {
		type: Array,
		default: () => [],
	},
})

const maxBirthDate = computed(() => {
	const today = new Date()
	return new Date(today.getFullYear() - 17, today.getMonth(), today.getDate())
})
</script>

<template>
	<section class="p-6 border-b xl:border-b-0 xl:border-r border-gray-200 dark:border-gray-700">
		<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-5">Identitas Pribadi</h3>

		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<BaseInputAdmin
				v-model="form.nama"
				label="Nama Anggota"
				type="text"
				placeholder="Isi dengan huruf"
				required
				@input="form.nama = onlyLetters(form.nama)"
				:error="getFieldError('nama', errors.nama)"
			/>

			<BaseInputAdmin v-model="form.jenis_kelamin" label="Jenis Kelamin" type="radio" required :selectables="genderOptions" :error="getFieldError('jenis_kelamin', errors.jenis_kelamin)" />

			<BaseInputAdmin
				v-model="form.nik"
				label="NIK"
				type="text"
				placeholder="Isi dengan angka"
				required
                max="16"
				@input="form.nik = onlyNumbers(form.nik)"
				:error="getFieldError('nik', errors.nik)"
			/>

			<BaseInputAdmin
				v-model="form.tempat_lahir"
				label="Tempat Lahir"
				type="text"
				placeholder="Isi tempat lahir"
				required
				:error="getFieldError('tempat_lahir', errors.tempat_lahir)"
			/>

			<BaseInputAdmin
				v-model="form.tgl_lahir"
				label="Tanggal Lahir"
				type="date"
				required
				:maxDate="maxBirthDate"
				:error="getFieldError('tgl_lahir', errors.tgl_lahir)"
			/>

			<BaseInputAdmin
				v-model="form.status_pernikahan"
				label="Status Perkawinan"
				type="select"
				required
				:selectables="maritalStatusOptions"
				:error="getFieldError('status_pernikahan', errors.status_pernikahan)"
			/>

			<BaseInputAdmin
				v-model="form.pendidikan_terakhir"
				label="Pendidikan Terakhir"
				type="select"
				required
				:selectables="educationOptions"
				:error="getFieldError('pendidikan_terakhir', errors.pendidikan_terakhir)"
			/>
		</div>
	</section>
</template>
