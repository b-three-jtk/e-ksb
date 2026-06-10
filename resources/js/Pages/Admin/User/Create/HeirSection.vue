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
	heirRelationshipOptions: {
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
				v-model="form.heir_nik"
				label="NIK Ahli Waris"
				type="text"
				placeholder="Isi dengan angka"
				required
                max="16"
				@input="form.heir_nik = onlyNumbers(form.heir_nik)"
				:error="getFieldError('heir_nik', errors.heir_nik)"
			/>

			<BaseInputAdmin
				v-model="form.heir_name"
				label="Nama Ahli Waris"
				type="text"
				placeholder="Isi dengan huruf"
				required
				@input="form.heir_name = onlyLetters(form.heir_name)"
				:error="getFieldError('heir_name', errors.heir_name)"
			/>

			<BaseInputAdmin
				v-model="form.heir_relationship"
				label="Hubungan Keluarga"
				type="select"
				required
				:selectables="heirRelationshipOptions"
				:error="getFieldError('heir_relationship', errors.heir_relationship)"
			/>

			<BaseInputAdmin
				v-model="form.heir_contact"
				label="Nomor Telepon Ahli Waris"
				type="text"
				placeholder="Contoh: 81234567890"
				required
				@input="form.heir_contact = normalizePhoneNumber(form.heir_contact, props.onlyNumbers)"
				:error="getFieldError('heir_contact', errors.heir_contact)"
			/>
		</div>
	</section>
</template>
