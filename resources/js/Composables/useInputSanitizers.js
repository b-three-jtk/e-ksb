export const useInputSanitizers = () => {
	const onlyLetters = (value) => {
		return value.replace(/[^a-zA-Z\s]/g, '')
	}

	const onlyNumbers = (value) => {
		return value.replace(/[^0-9]/g, '')
	}

	return {
		onlyLetters,
		onlyNumbers,
	}
}