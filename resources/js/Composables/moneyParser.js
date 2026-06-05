export default function moneyParser(value) {
    if (value === null || value === undefined || value === '') {
        return 'Rp0'
    }

    let num
    if (typeof value === 'string') {
        num = parseFloat(value.replace(/[^\d.-]/g, ''))
    } else {
        num = parseFloat(value)
    }

    if (isNaN(num)) {
        return 'Rp0'
    }

    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })

    return formatter.format(num)
}

moneyParser.parse = function parseMoney(value) {
    if (value === null || value === undefined || value === '') {
        return ''
    }

    const sanitized = String(value).replace(/[^\d,.-]/g, '').trim()

    if (sanitized === '') {
        return ''
    }

    const lastComma = sanitized.lastIndexOf(',')
    const lastDot = sanitized.lastIndexOf('.')
    let normalized = sanitized

    if (lastComma !== -1 && lastDot !== -1) {
        if (lastComma > lastDot) {
            normalized = normalized.replace(/\./g, '').replace(',', '.')
        } else {
            normalized = normalized.replace(/,/g, '')
        }
    } else if (lastDot !== -1) {
        normalized = normalized.replace(/\./g, '')
    } else if (lastComma !== -1) {
        normalized = normalized.replace(/,/g, '')
    }

    const parsed = Number.parseFloat(normalized)

    if (Number.isNaN(parsed)) {
        return ''
    }

    return String(Math.round(parsed))
}
