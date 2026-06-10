export function useFormatter() {
    const normalizePhoneNumber = (value, onlyNumbers) => {
        const digits = onlyNumbers(value)

        if (!digits) {
            return ''
        }

        if (digits.startsWith('0')) {
            return `62${digits.slice(1)}`
        }

        return digits.startsWith('62') ? digits : `62${digits}`
    }

    const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(date)
}

    return {
        normalizePhoneNumber,
        formatDate
    }
}
