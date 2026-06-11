const baseClass = 'font-semibold rounded-2xl px-4 text-theme-sm py-1'

const STATUS_MAP = {
    'Menunggu Kelengkapan Dokumen': {
        class: `${baseClass} text-yellow-600 bg-yellow-50`
    },
    'Belum Ditinjau': {
        class: `${baseClass} text-gray-600 bg-gray-100`
    },
    'Disetujui': {
        class: `${baseClass} text-green-600 bg-green-50`
    },
    'Disetujui dengan Catatan': {
        class: `${baseClass} text-orange-600 bg-orange-50`
    },
    'Ditolak': {
        class: `${baseClass} text-red-600 bg-red-50`
    },
    'Angsuran Berjalan': {
        class: `${baseClass} text-blue-600 bg-blue-50`
    },
    'Lunas': {
        class: `${baseClass} text-green-600 bg-green-50`
    }
}

export default function useFinancingStatus(status) {
    return STATUS_MAP[status]?.class || `${baseClass} text-gray-600 bg-gray-100`
}

export function getStatusLabel(status) {
    return STATUS_MAP[status] ? status : 'Tidak Diketahui'
}
