// Define language type
type LanguageCode = "en" // Supported language codes

// Define structure of language mapping object
interface Language {
	months: string[] // Array of months
	format: (month: string, day: number) => string // Function to format date
}

// Language mapping object
const languages: Record<LanguageCode, Language> = {
	en: {
		// English
		months: [
			"January",
			"February",
			"March",
			"April",
			"May",
			"June",
			"July",
			"August",
			"September",
			"October",
			"November",
			"December",
		],
		format: (month, day) => `${month} ${day}`,
	},
	//   th: { // Thai
	//     months: [
	//       "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
	//       "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
	//     ],
	//     format: (month, day) => `${day} ${month}`
	//   },
	//   vi: { // Vietnamese
	//     months: [
	//       "Tháng một", "Tháng hai", "Tháng ba", "Tháng tư", "Tháng năm", "Tháng sáu",
	//       "Tháng bảy", "Tháng tám", "Tháng chín", "Tháng mười", "Tháng mười một", "Tháng mười hai"
	//     ],
	//     format: (month, day) => `${month} ${day}`
	//   }
	// Can continue to add more languages as needed...
}

// Define function to convert date, accepts Chinese date and target language code, returns date in corresponding language format
export function getTranslatedDate(chineseDate: string, language: LanguageCode = "en"): string {
	// Extract month and day from date string
	const match = chineseDate.match(/(\d{1,2})月(\d{1,2})日/)

	if (match) {
		const month = parseInt(match[1], 10) // Get month
		const day = parseInt(match[2], 10) // Get day

		// Get selected language object, defaults to English if no language specified
		const selectedLanguage = languages[language]

		// Use language-specific months and format
		return selectedLanguage.format(selectedLanguage.months[month - 1], day)
	}
	return "Invalid date format"
}
