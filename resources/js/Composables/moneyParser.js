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
