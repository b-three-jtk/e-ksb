import { onBeforeUnmount, ref } from 'vue'

export const useImageUploadPreview = (form) => {
	const ktpInput = ref(null)
	const kkInput = ref(null)
	const ktpPreviewUrl = ref('')
	const kkPreviewUrl = ref('')

	const pickFile = (target) => {
		if (target === 'ktp') {
			ktpInput.value?.click()
			return
		}

		kkInput.value?.click()
	}

	const revokeKtpPreview = () => {
		if (ktpPreviewUrl.value) {
			URL.revokeObjectURL(ktpPreviewUrl.value)
			ktpPreviewUrl.value = ''
		}
	}

	const revokeKkPreview = () => {
		if (kkPreviewUrl.value) {
			URL.revokeObjectURL(kkPreviewUrl.value)
			kkPreviewUrl.value = ''
		}
	}

	const setFile = (event, target) => {
		const file = event.target.files?.[0] ?? null

		if (target === 'ktp') {
			form.ktp_photo = file
			revokeKtpPreview()
			if (file?.type?.startsWith('image/')) {
				ktpPreviewUrl.value = URL.createObjectURL(file)
			}
			return
		}

		form.kk_photo = file
		revokeKkPreview()
		if (file?.type?.startsWith('image/')) {
			kkPreviewUrl.value = URL.createObjectURL(file)
		}
	}

	const clearPreviews = () => {
		revokeKtpPreview()
		revokeKkPreview()
	}

	onBeforeUnmount(() => {
		clearPreviews()
	})

	return {
		ktpInput,
		kkInput,
		ktpPreviewUrl,
		kkPreviewUrl,
		pickFile,
		setFile,
		clearPreviews,
	}
}
