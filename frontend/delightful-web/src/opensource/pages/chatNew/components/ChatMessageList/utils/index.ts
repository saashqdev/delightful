export function isMessageInView(messageId: string, parentElement: HTMLElement | null) {
	if (!parentElement) return false

	const element = document.getElementById(messageId)
	if (!element) return false

	const rect = element.getBoundingClientRect()
	// Element's top enters viewport, judged as true
	return (
		(rect.top >= 0 && rect.top <= (parentElement.clientHeight || parentElement.scrollHeight)) ||
		(rect.bottom >= 0 &&
			rect.bottom <= (parentElement.clientHeight || parentElement.scrollHeight))
	)
}
