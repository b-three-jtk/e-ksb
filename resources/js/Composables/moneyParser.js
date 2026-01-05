function parseCurrencyAmount(str) {
    const num = parseFloat(str);
    if (Number.isNaN(num)) {
        return '';
    }
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    });
    return formatter.format(num);
}

export default parseCurrencyAmount;
