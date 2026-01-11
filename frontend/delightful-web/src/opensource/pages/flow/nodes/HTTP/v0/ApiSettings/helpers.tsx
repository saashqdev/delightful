/** Find strings wrapped in {} */
export function findBracedStrings(inputString: string) {
	const regex = /\{([^}]+)\}/g
	const matches = []
	let match

	// eslint-disable-next-line no-cond-assign
	while ((match = regex.exec(inputString)) !== null) {
		matches.push(match[1])
	}

	return matches
}

export default {}





