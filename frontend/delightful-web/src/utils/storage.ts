const PLATEFORM_PREFIX = "DELIGHTFUL"

export const platformKey = (str: string) => `${PLATEFORM_PREFIX}:${str}`

export const wrapperVersion = (data: any, version = 1) => {
	return { data, version }
}

export const unwrapperVersion = (data: any) => {
	return data.data
}
