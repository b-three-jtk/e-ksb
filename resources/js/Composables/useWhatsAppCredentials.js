export const useWhatsAppCredentials = (toast) => {
	const normalizeWhatsAppNumber = (phoneNumber) => {
		const digits = String(phoneNumber || '').replace(/\D/g, '')
		if (!digits) {
			return ''
		}

		if (digits.startsWith('62')) {
			return digits
		}

		if (digits.startsWith('0')) {
			return `62${digits.slice(1)}`
		}

		return digits
	}

	const sendCredentialsToWhatsApp = (credentials) => {
		const waNumber = normalizeWhatsAppNumber(credentials?.phone_number)
		if (!waNumber) {
			toast('Nomor WhatsApp anggota tidak valid.', {
				type: 'error',
				position: 'bottom-right',
				transition: 'slide',
				dangerouslyHTMLString: true,
			})
			return
		}

		const message = [
			'Halo, akun anggota Anda sudah dibuat.',
			`Nama: ${credentials?.name ?? '-'}`,
			`Nomor Anggota: ${credentials?.user_code ?? '-'}`,
			`Password Awal: ${credentials?.initial_password ?? '-'}`,
			'Silakan login dan segera ubah password Anda.',
		].join('\n')

		const url = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`
		window.open(url, '_blank', 'noopener,noreferrer')
	}

	return {
		sendCredentialsToWhatsApp,
	}
}
