import { reactive, watch } from "vue";

export function useCreateAdminValidation(form: any) {
    const errors = reactive({
        email: "",
        nik: "",
        nama_lengkap: "",
        role_id: "",
        work_unit_id: "",
        nama_lembaga: "",
    });

    watch(
        () => form.email,
        (v) => {
            const value = v?.trim() || "";

            if (!value) {
                errors.email = "Email wajib diisi";
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errors.email = "Format email tidak valid";
            } else {
                errors.email = "";
            }
        }
    );

    watch(
        () => form.nik,
        (v) => {
            if (!v) {
                errors.nik = "NIK wajib diisi";
            } else if (!/^\d+$/.test(v)) {
                errors.nik = "NIK hanya boleh berisi angka";
            } else if (v.length !== 16) {
                errors.nik = "NIK harus 16 digit";
            } else {
                errors.nik = "";
            }
        }
    );

    watch(
        () => form.nama_lengkap,
        (v) => {
            errors.nama_lengkap = v ? "" : "Nama lengkap wajib diisi";
        }
    );

    watch(
        () => form.role_id,
        (v) => {
            errors.role_id = v ? "" : "Posisi wajib diisi";
        }
    );

    watch(
        () => form.work_unit_id,
        (v) => {
            errors.work_unit_id = v ? "" : "Unit kerja wajib diisi";
        }
    );

    watch(
        () => form.nama_lembaga,
        (v) => {
            errors.nama_lembaga = v ? "" : "Nama lembaga wajib diisi";
        }
    );

    return { errors };
}
