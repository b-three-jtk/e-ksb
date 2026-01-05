import { reactive, watch } from 'vue'

export function useRegisterValidation(form: any) {
  const errors = reactive({
    email: '',
    nik: '',
    password: '',
    nama_lengkap: '',
    work_unit_id: '',
    nama_lembaga: '',
    password_confirmation: '',
  })

    watch(() => form.email, (v) => {
    const value = v?.trim() || ''

    if (!value) {
        errors.email = 'Email wajib diisi'
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        errors.email = 'Format email tidak valid'
    } else {
        errors.email = ''
    }
    })

  watch(() => form.nik, (v) => {
    if (!v) {
      errors.nik = 'NIK wajib diisi'
    } else if (!/^\d+$/.test(v)) {
      errors.nik = 'NIK hanya boleh berisi angka'
    } else if (v.length !== 16) {
      errors.nik = 'NIK harus 16 digit'
    } else {
      errors.nik = ''
    }
  })

  watch(() => form.password, (v) => {
    if (!v) {
      errors.password = 'Password wajib diisi'
    } else if (v.length < 8) {
      errors.password = 'Password minimal 8 karakter'
    } else {
      errors.password = ''
    }
  })

  watch(
    () => form.password_confirmation,
    (v) => {
      if (!v) {
        errors.password_confirmation = ''
      } else if (v !== form.password) {
        errors.password_confirmation = 'Password tidak sama'
      } else {
        errors.password_confirmation = ''
      }
    }
  )

  watch(
    () => form.password,
    () => {
      if (
        form.password_confirmation &&
        form.password_confirmation !== form.password
      ) {
        errors.password_confirmation = 'Password tidak sama'
      } else {
        errors.password_confirmation = ''
      }
    }
  )

  watch(() => form.nama_lengkap, (v) => {
    errors.nama_lengkap = v ? '' : 'Nama lengkap wajib diisi'
  })

  watch(() => form.work_unit_id, (v) => {
    errors.work_unit_id = v ? '' : 'Unit kerja wajib diisi'
  })

  watch(() => form.nama_lembaga, (v) => {
    errors.nama_lembaga = v ? '' : 'Nama lembaga wajib diisi'
  })

  return { errors }
}
