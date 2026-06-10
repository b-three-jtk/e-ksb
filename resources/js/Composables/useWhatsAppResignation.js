export function useWhatsAppResignation(toast) {
    const sendResignationToWhatsApp = (data) => {
        let phone = data.phone_number?.replace(/\D/g, '')
        if (!phone) {
            toast.error('Nomor WhatsApp tidak ditemukan')
            return
        }
        if (phone.startsWith('0')) {
            phone = '62' + phone.slice(1)
        }

        const savingsLine = data.total_savings > 0
            ? `\nAnda masih mempunyai simpanan sebesar Rp ${Number(data.total_savings).toLocaleString('id-ID')} yang dapat diambil kapan saja. Silakan kunjungi koperasi untuk informasi lebih lanjut.`
            : ''

        const message = encodeURIComponent(
`Assalamu'alaikum, ${data.name}.

Kami informasikan bahwa permohonan pengunduran diri Anda sebagai anggota koperasi telah *disetujui*.

Nomor Anggota: ${data.user_code}${savingsLine}

Terima kasih atas kontribusi Anda selama ini.`)

        window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
    }

    return { sendResignationToWhatsApp }
}
