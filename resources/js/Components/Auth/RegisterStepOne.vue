<script setup>
import BaseInput from '@/Components/Form/BaseInput.vue'
import BaseSelect from '@/Components/Form/BaseSelect.vue'
import { useRegisterValidation } from '@/Components/Auth/useRegisterValidation'

const props = defineProps({
  form: Object,
  workUnits: {
    type: Array,
    default: () => []
  }
})

defineEmits(['next'])

const { errors } = useRegisterValidation(props.form)
</script>

<template>
  <form
    @submit.prevent="$emit('next')"
    class="grid grid-cols-1 md:grid-cols-2 gap-4"
  >

    <h2 class="text-2xl font-bold text-orange-500">
        Create an Account
    </h2>

    <BaseInput
      v-model="form.email"
      label="Email"
      type="email"
      required
      :error="errors.email || form.errors.email"
    />

    <BaseInput
      v-model="form.nama_lengkap"
      label="Nama Lengkap"
      required
      :error="errors.nama_lengkap"
    />

    <BaseInput
      v-model="form.nik"
      label="NIK"
      required
      :error="errors.nik"
    />

    <BaseSelect
      v-model="form.work_unit_id"
      label="Unit Kerja"
      required
      :error="errors.work_unit_id"
    >
      <option
        v-for="unit in workUnits"
        :key="unit.id"
        :value="unit.id"
      >
        {{ unit.name }}
      </option>
    </BaseSelect>

    <BaseInput
      v-model="form.nama_lembaga"
      label="Nama Lembaga"
      required
      :error="errors.nama_lembaga"
    />

    <BaseInput
      v-model="form.password"
      label="Password"
      type="password"
      :error="errors.password"
    />

    <BaseInput
      v-model="form.password_confirmation"
      label="Confirm Password"
      type="password"
      :error="errors.password_confirmation"
    />
  </form>
</template>
