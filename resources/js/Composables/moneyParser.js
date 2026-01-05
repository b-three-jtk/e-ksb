function parseCurrencyAmount(str) {
  // Remove all characters except digits, the decimal point, and the negative sign
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    });
    const amount = formatter.format(parseFloat(str));
    return amount === 'RpNaN' ? '' : amount;
}

export default parseCurrencyAmount;
