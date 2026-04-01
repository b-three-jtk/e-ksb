<script setup>
import { Icon } from '@iconify/vue'
import { ref } from 'vue'

const ktpInput = ref(null)
const kkInput = ref(null)

const openPicker = (target) => {
	if (target === 'ktp') {
		ktpInput.value?.click()
		return
	}

	kkInput.value?.click()
}

defineProps({
	form: {
		type: Object,
		required: true,
	},
	ktpPreviewUrl: {
		type: String,
		default: '',
	},
	kkPreviewUrl: {
		type: String,
		default: '',
	},
	setFile: {
		type: Function,
		required: true,
	},
	openImageOptions: {
		type: Function,
		required: true,
	},
})
</script>

<template>
	<section class="p-6 border-b xl:border-b-0 border-gray-200 dark:border-gray-700">
		<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-5">Berkas Pendukung</h3>

		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<div>
				<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Foto KTP</label>
				<input ref="ktpInput" type="file" class="hidden" accept="image/*,.pdf" @change="setFile($event, 'ktp')" />
				<div v-if="ktpPreviewUrl" class="mb-3">
					<button type="button" class="w-full" @click="openImageOptions('ktp', () => openPicker('ktp'))">
						<img
							:src="ktpPreviewUrl"
							alt="Preview KTP"
							class="h-50 w-full rounded-lg border border-gray-200 object-cover dark:border-gray-700 hover:opacity-90"
						/>
					</button>
					<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Klik gambar untuk lihat detail atau ganti gambar</p>
				</div>
				<button
					v-if="!ktpPreviewUrl"
					type="button"
					@click="openPicker('ktp')"
					class="h-11 w-full md:w-44 rounded-lg bg-gray-500 text-white text-sm font-semibold hover:bg-gray-600 inline-flex items-center justify-center gap-2"
				>
					<Icon icon="mdi:upload" class="w-4 h-4" />
					Unggah
				</button>
				<p v-if="form.ktp_photo" class="text-xs text-gray-500 dark:text-gray-400 mt-2 truncate">{{ form.ktp_photo.name }}</p>
			</div>

			<div>
				<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Foto KK</label>
				<input ref="kkInput" type="file" class="hidden" accept="image/*,.pdf" @change="setFile($event, 'kk')" />
				<div v-if="kkPreviewUrl" class="mb-3">
					<button type="button" class="w-full" @click="openImageOptions('kk', () => openPicker('kk'))">
						<img
							:src="kkPreviewUrl"
							alt="Preview KK"
							class="h-50 w-full rounded-lg border border-gray-200 object-cover dark:border-gray-700 hover:opacity-90"
						/>
					</button>
					<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Klik gambar untuk lihat detail atau ganti gambar</p>
				</div>
				<button
					v-if="!kkPreviewUrl"
					type="button"
					@click="openPicker('kk')"
					class="h-11 w-full md:w-44 rounded-lg bg-gray-500 text-white text-sm font-semibold hover:bg-gray-600 inline-flex items-center justify-center gap-2"
				>
					<Icon icon="mdi:upload" class="w-4 h-4" />
					Unggah
				</button>
				<p v-if="form.kk_photo" class="text-xs text-gray-500 dark:text-gray-400 mt-2 truncate">{{ form.kk_photo.name }}</p>
			</div>
		</div>
	</section>
</template>
