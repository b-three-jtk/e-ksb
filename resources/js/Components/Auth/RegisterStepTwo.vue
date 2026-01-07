<script setup>
import { ref } from 'vue'

const contohImage = new URL(
  '/public/images/auth/example.png',
  import.meta.url
).href
const props = defineProps({
  form: Object
})

const previewPribadi = ref(null)
const previewKtp = ref(null)

const onUploadPribadi = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    alert('File harus berupa gambar')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    alert('Ukuran file maksimal 2MB')
    return
  }

  props.form.foto_pribadi = file
  previewPribadi.value = URL.createObjectURL(file)
}

const onUploadKtp = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.startsWith('image/')) return
  if (file.size > 2 * 1024 * 1024) return

  props.form.foto_ktp = file
  previewKtp.value = URL.createObjectURL(file)
}

const placeholderImage = new URL(
  '/public/images/auth/picture_filled.png',
  import.meta.url
).href

</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-[1fr_2fr] gap-8 items-start">
    
    <div class="bg-gray-100 rounded-lg p-4">
      <p class="text-sm text-gray-600 mb-2">Contoh :</p>

        <div class="w-full h-64 bg-white rounded-md overflow-hidden">
            <img
                :src="contohImage"
                alt="Contoh Upload"
                class="w-full h-full object-contain"
            />
        </div>


      <p class="mt-3 text-xs text-gray-500 font-body">
        * Foto pribadi & KTP harus terlihat jelas
      </p>
    </div>

    <div>
        <h2 class="text-2xl font-bold text-orange-500 mb-12 mt-2 text-right font-head">
            Upload Foto Diri & KTP
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-[1fr_1.5fr] gap-6 items-start">
            <div class="text-center">
            <input
                type="file"
                accept="image/*"
                class="hidden"
                id="foto_pribadi"
                @change="onUploadPribadi"
            />

            <label
                for="foto_pribadi"
                class="mx-auto flex items-center justify-center
                    aspect-square w-40
                    rounded-lg bg-gray-200 cursor-pointer overflow-hidden"
            >
                <img
                v-if="previewPribadi"
                :src="previewPribadi"
                class="object-cover w-full h-full"
                />

                <img
                v-else
                :src="placeholderImage"
                alt="Upload Foto Pribadi"
                class="w-8 h-8 opacity-50"
                />
            </label>

            <p class="mt-2 text-sm text-blue-900 font-medium font-body">
                Foto Pribadi
            </p>
            </div>

            <div class="text-center">
            <input
                type="file"
                accept="image/*"
                class="hidden"
                id="foto_ktp"
                @change="onUploadKtp"
            />

            <label
                for="foto_ktp"
                class="mx-auto flex items-center justify-center
                    aspect-[16/10] w-full max-w-md
                    rounded-lg bg-gray-200 cursor-pointer overflow-hidden"
            >
                <img
                v-if="previewKtp"
                :src="previewKtp"
                class="object-cover w-full h-full"
                />

                <img
                v-else
                :src="placeholderImage"
                alt="Upload Foto KTP"
                class="w-8 h-8 opacity-50"
                />
            </label>

            <p class="mt-2 text-sm text-blue-900 font-medium font-body">
                Foto KTP
            </p>
            </div>
        </div>
    </div>
  </div>
</template>
