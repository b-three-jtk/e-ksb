import { reactive, watch } from "vue";

type UserValidationOptions = {
    requireEmail?: boolean;
};

export function useUserValidation(form: any, options: UserValidationOptions = {}) {
    const errors = reactive({
        email: "",
        nik: "",
        heir_nik: "",
        name: "",
        role_id: "",
        phone_number: "",
        profile_picture: "",
    });

    watch(
        () => form.email,
        (v) => {
            const value = v?.trim() || "";

            if (!value) {
                errors.email = "";
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
        () => form.heir_nik,
        (v) => {
            if (!v) {
                errors.heir_nik = "NIK ahli waris wajib diisi";
            } else if (!/^\d+$/.test(v)) {
                errors.heir_nik = "NIK ahli waris hanya boleh berisi angka";
            } else if (v.length !== 16) {
                errors.heir_nik = "NIK ahli waris harus 16 digit";
            } else {
                errors.heir_nik = "";
            }
        }
    );

    watch(
        () => form.name,
        (v) => {
            errors.name = v ? "" : "Nama lengkap wajib diisi";
        }
    );

    watch(
        () => form.role_id,
        (v) => {
            errors.role_id = v ? "" : "Posisi wajib diisi";
        }
    );

    watch(
        () => form.phone_number,
        (v) => {
            const value = v?.trim() || "";

            if (!/^\+?\d{6,14}$/.test(value)) {
                errors.phone_number = "Format nomor telepon tidak valid";
            } else {
                errors.phone_number = "";
            }
        }
    );

    watch(
        () => form.profile_picture,
        (v) => {
            if (typeof v === 'string') {
                errors.profile_picture = "";
                return;
            }

            if (v && v instanceof File) {
                if (!(v.type === "image/png" || v.type === "image/jpeg" || v.type === "image/jpg" || v.type === "image/gif")) {
                    errors.profile_picture = "Hanya format JPG atau PNG yang diperbolehkan";
                } else if (v.size > 2 * 1024 * 1024) {
                    errors.profile_picture = "Ukuran gambar maksimal 2MB";
                } else {
                    errors.profile_picture = "";
                }
            } else {
                errors.profile_picture = "";
            }
        }
    );

    return { errors };
}
