<script setup>
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
</script>

<template>
	<section class="p-6 border-b xl:border-b-0 xl:border-r border-gray-200 dark:border-gray-700">
		<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-5">Identitas Pribadi</h3>

		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<BaseInputAdmin
				v-model="form.name"
				label="Nama Anggota"
				type="text"
				placeholder="Isi dengan huruf"
				required
				@input="form.name = onlyLetters(form.name)"
				:error="getFieldError('name', errors.name)"
			/>

			<BaseInputAdmin v-model="form.gender" label="Jenis Kelamin" type="radio" required :selectables="genderOptions" :error="getFieldError('gender', errors.gender)" />

			<BaseInputAdmin
				v-model="form.nik"
				label="NIK"
				type="text"
				placeholder="Isi dengan angka"
				required
				@input="form.nik = onlyNumbers(form.nik)"
				:error="getFieldError('nik', errors.nik)"
			/>

			<BaseInputAdmin
				v-model="form.birth_place"
				label="Tempat Lahir"
				type="text"
				placeholder="Isi tempat lahir"
				required
				:error="getFieldError('birth_place', errors.birth_place)"
			/>

			<BaseInputAdmin
				v-model="form.birth_date"
				label="Tanggal Lahir"
				type="date"
				required
				:error="getFieldError('birth_date', errors.birth_date)"
			/>

			<BaseInputAdmin
				v-model="form.marital_status"
				label="Status Perkawinan"
				type="select"
				required
				:selectables="maritalStatusOptions"
				:error="getFieldError('marital_status', errors.marital_status)"
			/>

			<!-- Nama Pasangan, tampil ketika status = Kawin -->
			<BaseInputAdmin
				v-if="form.marital_status === 'Kawin'"
				v-model="form.spouse_name"
				label="Nama Pasangan"
				type="text"
				placeholder="Isi nama pasangan"
				@input="form.spouse_name = onlyLetters(form.spouse_name)"
				:error="getFieldError('spouse_name', errors.spouse_name)"
			/>

			<BaseInputAdmin
				v-model="form.last_education"
				label="Pendidikan Terakhir"
				type="select"
				required
				:selectables="educationOptions"
				:error="getFieldError('last_education', errors.last_education)"
			/>
		</div>
	</section>
</template>
