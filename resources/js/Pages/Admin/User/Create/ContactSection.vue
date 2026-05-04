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
	onlyNumbers: {
		type: Function,
		required: true,
	},
})
</script>

<template>
	<section class="p-6 border-b xl:border-b-0 xl:border-r border-gray-200 dark:border-gray-700">
		<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-5">Kontak</h3>

		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<BaseInputAdmin
				v-model="form.phone_number"
				label="Nomor Telepon"
				type="text"
				placeholder="Isi dengan angka"
				required
				@input="form.phone_number = onlyNumbers(form.phone_number)"
				:error="getFieldError('phone_number', errors.phone_number)"
			/>

			<BaseInputAdmin
				v-model="form.email"
				label="Email"
				type="email"
				placeholder="Opsional, isi jika ada"
				:error="getFieldError('email', errors.email)"
			/>

			<BaseInputAdmin
				v-model="form.domicile_address"
				class="md:col-span-2"
				label="Alamat Sesuai KTP"
				type="text"
				placeholder="Isi dengan huruf dan angka"
				required
				:error="getFieldError('domicile_address', errors.domicile_address)"
			/>

			<BaseInputAdmin
				v-model="form.residential_address"
				class="md:col-span-2"
				label="Alamat Domisili"
				type="text"
				placeholder="Isi dengan huruf dan angka"
				:error="getFieldError('residential_address', errors.residential_address)"
			/>
		</div>
	</section>
</template>
