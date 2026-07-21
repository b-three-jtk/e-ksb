export const useInputSanitizers = () => {
	const onlyLetters = (value) => {
		if (typeof value !== 'string') return ''
		return value.replace(/[^a-zA-Z\s]/g, '')
	}

	const onlyNumbers = (value) => {
		if (typeof value !== 'string') return ''
		return value.replace(/[^0-9]/g, '')
	}

	const onlyAddress = (value) => {
		if (typeof value !== 'string') return ''
		return value.replace(/[^a-zA-Z0-9\s\-.,/]/g, '')
	}

	return {
		onlyLetters,
		onlyNumbers,
		onlyAddress,
	}
}