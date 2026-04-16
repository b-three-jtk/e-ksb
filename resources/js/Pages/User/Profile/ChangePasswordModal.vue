<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { toast } from 'vue3-toastify'

const props = defineProps({
	isOpen: {
		type: Boolean,
		required: true,
	},
})

const emit = defineEmits(['close'])

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const form = useForm({
	current_password: '',
	password: '',
	password_confirmation: '',
})

watch(() => props.isOpen, (newVal) => {
	if (newVal) {
		document.addEventListener('keydown', handleEscapeKey)
	} else {
		document.removeEventListener('keydown', handleEscapeKey)
	}
})

// Password validation helpers
const hasMinLength = (pwd) => pwd.length >= 8
const hasUpperCase = (pwd) => /[A-Z]/.test(pwd)
const hasNumber = (pwd) => /[0-9]/.test(pwd)
const hasSpecialChar = (pwd) => /[!@#$%^&*()\-_=+\[\]{};:'",.<>?/\\|`~]/.test(pwd)

const isConfirmPasswordMismatch = computed(() => {
	if (!form.password_confirmation) {
		return false
	}

	return form.password !== form.password_confirmation
})

const closeModal = () => {
	form.reset()
	showCurrentPassword.value = false
	showNewPassword.value = false
	showConfirmPassword.value = false
	emit('close')
}

const submitPasswordChange = () => {
	if (isConfirmPasswordMismatch.value) {
		return
	}

	form.post('/user/profile/update-password', {
		onSuccess: () => {
			closeModal()
		},
		onError: () => {
			const firstError = Object.values(form.errors || {})[0]
			toast(firstError || 'Gagal mengubah password.', {
				type: 'error',
				position: 'bottom-right',
				transition: 'slide',
				dangerouslyHTMLString: true,
			})
		},
	})
}

const handleEscapeKey = (e) => {
	if (e.key === 'Escape') {
		closeModal()
	}
}
</script>

<template>
	<Transition name="modal-fade">
		<div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/45 p-4 backdrop-blur-[1px]">
			<div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl dark:bg-gray-800">
					<!-- Modal Header -->
					<div class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-700">
						<div class="flex items-center gap-3">
							<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-lime-200">
								<Icon icon="mdi:lock" class="h-5 w-5 text-emerald-700" />
							</div>
							<h2 class="text-lg font-bold text-slate-800 dark:text-white">Ubah Password</h2>
						</div>
						<button
							type="button"
							@click="closeModal"
							class="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
						>
							<Icon icon="mdi:close" class="w-6 h-6" />
						</button>
					</div>

					<!-- Modal Body -->
					<form @submit.prevent="submitPasswordChange" class="space-y-4 p-6">
						<!-- Current Password -->
						<div>
							<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
								Password saat ini
							</label>
							<div class="relative">
								<input
									:type="showCurrentPassword ? 'text' : 'password'"
									v-model="form.current_password"
									class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
									:class="{ 'border-red-500': form.errors.current_password }"
									placeholder="Masukan Password Saat Ini"
								/>
								<button
									type="button"
									@click="showCurrentPassword = !showCurrentPassword"
									class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
								>
									<Icon :icon="showCurrentPassword ? 'mdi:eye' : 'mdi:eye-off'" class="w-5 h-5" />
								</button>
							</div>
							<p v-if="form.errors.current_password" class="text-xs text-red-500 mt-1">
								{{ form.errors.current_password }}
							</p>
						</div>

						<!-- New Password -->
						<div>
							<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
								Password baru
							</label>
							<div class="relative">
								<input
									:type="showNewPassword ? 'text' : 'password'"
									v-model="form.password"
									class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
									:class="{ 'border-red-500': form.errors.password }"
									placeholder="Masukan Password Baru"
								/>
								<button
									type="button"
									@click="showNewPassword = !showNewPassword"
									class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
								>
									<Icon :icon="showNewPassword ? 'mdi:eye' : 'mdi:eye-off'" class="w-5 h-5" />
								</button>
							</div>

							<!-- Password Strength Indicator -->
							<div v-if="form.password" class="mt-2.5 space-y-2">
								<div class="text-xs space-y-1">
									<div :class="hasMinLength(form.password) ? 'text-green-600 dark:text-green-400' : 'text-gray-500'">
										<Icon icon="mdi:check-circle-outline" class="w-3.5 h-3.5 inline mr-1" />
										Minimal 8 karakter
									</div>
									<div :class="hasUpperCase(form.password) ? 'text-green-600 dark:text-green-400' : 'text-gray-500'">
										<Icon icon="mdi:check-circle-outline" class="w-3.5 h-3.5 inline mr-1" />
										Mengandung huruf kapital
									</div>
									<div :class="hasNumber(form.password) ? 'text-green-600 dark:text-green-400' : 'text-gray-500'">
										<Icon icon="mdi:check-circle-outline" class="w-3.5 h-3.5 inline mr-1" />
										Mengandung angka
									</div>
									<div :class="hasSpecialChar(form.password) ? 'text-green-600 dark:text-green-400' : 'text-gray-500'">
										<Icon icon="mdi:check-circle-outline" class="w-3.5 h-3.5 inline mr-1" />
										Mengandung simbol (opsional, disarankan)
									</div>
								</div>
							</div>

							<p v-if="form.errors.password" class="text-xs text-red-500 mt-1">
								{{ form.errors.password }}
							</p>
						</div>

						<!-- Confirm Password -->
						<div>
							<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
								Konfirmasi Password Baru
							</label>
							<div class="relative">
								<input
									:type="showConfirmPassword ? 'text' : 'password'"
									v-model="form.password_confirmation"
									class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
									:class="{ 'border-red-500': form.errors.password_confirmation || isConfirmPasswordMismatch }"
									placeholder="Konfirmasi Password Baru"
								/>
								<button
									type="button"
									@click="showConfirmPassword = !showConfirmPassword"
									class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
								>
									<Icon :icon="showConfirmPassword ? 'mdi:eye' : 'mdi:eye-off'" class="w-5 h-5" />
								</button>
							</div>
							<p v-if="isConfirmPasswordMismatch" class="mt-1 text-xs text-red-500">
								Konfirmasi password belum sesuai dengan password baru.
							</p>
							<p v-else-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">
								{{ form.errors.password_confirmation }}
							</p>
						</div>

						<!-- Modal Footer -->
						<div class="flex gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
							<button
								type="button"
								@click="closeModal"
								class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
							>
								Batal
							</button>
							<button
								type="submit"
								:disabled="form.processing || isConfirmPasswordMismatch"
								class="flex-1 px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:bg-green-400 transition-colors"
							>
								{{ form.processing ? 'Menyimpan...' : 'Simpan' }}
							</button>
						</div>
					</form>
			</div>
		</div>
	</Transition>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
	transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
	opacity: 0;
}

.modal-fade-enter-to,
.modal-fade-leave-from {
	opacity: 1;
}

/* Hide native password reveal buttons so only custom eye icon is shown. */
input[type='password']::-ms-reveal,
input[type='password']::-ms-clear {
	display: none;
}

input[type='password']::-webkit-credentials-auto-fill-button,
input[type='password']::-webkit-contacts-auto-fill-button {
	visibility: hidden;
	display: none !important;
	pointer-events: none;
}
</style>
