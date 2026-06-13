<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Layout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Button from '@/Components/Form/Button.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import { useInputSanitizers } from '@/Composables/useInputSanitizers'
import { useImageUploadPreview } from '@/Composables/useImageUploadPreview'
import PersonalIdentitySection from './PersonalIdentitySection.vue'
import SupportingDocument from './SupportingDocument.vue'
import ContactSection from './ContactSection.vue'
import HeirSection from './HeirSection.vue'

const props = defineProps({
	educationOptions: {
		type: Array,
		default: () => [],
	},
	maritalStatusOptions: {
		type: Array,
		default: () => [],
	},
	heirRelationshipOptions: {
		type: Array,
		default: () => [],
	},
})

const requiredMemberFields = computed(() => [
	'name',
	'gender',
	'nik',
	'birth_place',
	'birth_date',
	'marital_status',
	'phone_number',
	'domicile_address',
	'last_education',
	'heir_nik',
	'heir_name',
	'heir_relationship',
	'heir_contact',
])

const form = useForm({
	name: '',
	gender: '',
	nik: '',
	birth_place: '',
	birth_date: '',
	marital_status: '',
	phone_number: '',
	email: '',
	domicile_address: '',
	last_education: '',
	residential_address: '',
	heir_nik: '',
	heir_name: '',
	heir_relationship: '',
	heir_contact: '',
	ktp_photo: null,
	kk_photo: null,
})

const breadcrumbItems = [
	{ name: 'Dashboard', link: '/admin/dashboard' },
	{ name: 'Anggota', link: '/admin/users' },
	{ name: 'Tambah Anggota' },
]

const genderOptions = [
	{ value: 'Laki-laki', text: 'Laki-laki' },
	{ value: 'Perempuan', text: 'Perempuan' },
]

const { errors } = useUserValidation(form, { requireEmail: false })
const { onlyLetters, onlyNumbers } = useInputSanitizers()
const { ktpPreviewUrl, kkPreviewUrl, setFile } = useImageUploadPreview(form)

const getFieldError = (field, fallback = '') => {
	return form.errors[field] || fallback
}

const openImageOptions = (target, onReplace) => {
	const previewUrl = target === 'ktp' ? ktpPreviewUrl.value : kkPreviewUrl.value
	const selectedFile = target === 'ktp' ? form.ktp_photo : form.kk_photo

	if (!previewUrl || !selectedFile) {
		return
	}

	Swal.fire({
		title: 'Pilih Aksi',
		text: 'Anda ingin melihat detail gambar atau mengganti gambar?',
		icon: 'question',
		showCancelButton: true,
		showDenyButton: true,
		confirmButtonText: 'Lihat Detail',
		denyButtonText: 'Ganti Gambar',
		cancelButtonText: 'Tutup',
		confirmButtonColor: '#007943',

	}).then((result) => {
		if (result.isConfirmed) {
			Swal.fire({
				imageUrl: previewUrl,
				imageAlt: 'Preview gambar',
				text: `Ukuran file: ${(selectedFile.size / 1024).toFixed(1)} KB`,
				confirmButtonText: 'Tutup',
				confirmButtonColor: '#007943',
			})
			return
		}

		if (result.isDenied) {
			onReplace?.()
		}
	})
}

const isSaveDisabled = computed(() => {
	const hasEmptyRequiredFields = requiredMemberFields.value.some((field) => !String(form[field] ?? '').trim())
	const hasMissingDocuments = !form.ktp_photo || !form.kk_photo

	return form.processing || hasEmptyRequiredFields || hasMissingDocuments
})

const submitForm = () => {
	if (isSaveDisabled.value) {
		return
	}

	Swal.fire({
		title: 'Konfirmasi',
		text: 'Apakah Anda yakin ingin menambahkan data anggota ini?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Ya, tambahkan',
		cancelButtonText: 'Batal',
		confirmButtonColor: '#007943',
	}).then((result) => {
		if (result.isConfirmed) {
			form.post('/admin/users/store', {
				onError: () => {
					const firstError = Object.values(form.errors || {})[0]
					toast(firstError || 'Gagal menambahkan anggota.', {
						type: 'error',
						position: 'bottom-right',
						transition: 'slide',
						dangerouslyHTMLString: true,
					})
				},
			})
		}
	})
}
</script>

<template>
	<Layout title="Tambah Anggota">
		<div class="flex flex-col gap-6">
			<PageBreadcrumb page-title="Tambah Anggota" :items="breadcrumbItems" />

			<div class="card-layout">
				<div class="grid grid-cols-1 xl:grid-cols-2 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
					<PersonalIdentitySection
						:form="form"
						:errors="errors"
						:get-field-error="getFieldError"
						:only-letters="onlyLetters"
						:only-numbers="onlyNumbers"
						:gender-options="genderOptions"
						:marital-status-options="props.maritalStatusOptions"
						:education-options="props.educationOptions"
					/>

					<SupportingDocument
						:form="form"
						:ktp-preview-url="ktpPreviewUrl"
						:kk-preview-url="kkPreviewUrl"
						:set-file="setFile"
						:open-image-options="openImageOptions"
					/>
				</div>

				<div class="mt-5 grid grid-cols-1 xl:grid-cols-2 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
					<ContactSection
						:form="form"
						:errors="errors"
						:get-field-error="getFieldError"
						:only-numbers="onlyNumbers"
					/>

					<HeirSection
						:form="form"
						:errors="errors"
						:get-field-error="getFieldError"
						:only-letters="onlyLetters"
						:only-numbers="onlyNumbers"
						:heir-relationship-options="props.heirRelationshipOptions"
					/>
				</div>

				<div class="mt-8 flex items-center justify-end gap-4">
					<Button href="/admin/users" variant="light" size="medium">
						Batal
					</Button>
					<Button @click="submitForm" :disabled="isSaveDisabled" variant="secondary" size="medium">
						{{ form.processing ? 'Menyimpan...' : 'Simpan' }}
					</Button>
				</div>
			</div>
		</div>
	</Layout>
</template>
