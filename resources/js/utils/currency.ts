/**
 * Format number as Indonesian Rupiah currency
 * @param value - The numeric value to format
 * @returns Formatted currency string (e.g., "Rp1.000.000")
 */
export const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}
