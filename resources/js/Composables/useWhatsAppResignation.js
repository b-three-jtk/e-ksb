export function useWhatsAppResignation(toast) {
    const sendResignationToWhatsApp = (data) => {
        const phone = data.phone?.replace(/\D/g, '')
        if (!phone) {
            toast.error('Nomor WhatsApp tidak ditemukan')
            return
        }

        const message = encodeURIComponent(
            `Yth. ${data.name},\n\nPengunduran diri Anda sebagai anggota koperasi telah disetujui.\n\nNomor Anggota: ${data.user_code}\n\nTerima kasih atas kontribusi Anda selama ini.`
        )

        window.open(`https://wa.me/${phone}?text=${message}`, '_blank')
    }

    return { sendResignationToWhatsApp }
}
