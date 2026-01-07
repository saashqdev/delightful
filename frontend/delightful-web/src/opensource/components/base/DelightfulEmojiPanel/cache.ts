import emojiJsons from "./emojis.json"

// emoji locale cache
export const emojiLocaleCache = new Map()
// emoji file path cache
export const emojiFilePathCache = new Map()
// emoji skin tone cache
export const emojiSkinTones = new Map()

// Initialize emoji locale cache
emojiJsons.emojis.forEach(({ code, names, skinTones, filePath }) => {
	emojiLocaleCache.set(code, names)
	emojiSkinTones.set(code, skinTones)
	emojiFilePathCache.set(code, filePath)
})
