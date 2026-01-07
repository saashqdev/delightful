export type EmojiJsonBase = {
	names: Record<string, string>
	filePath: string
	code: string
}

export type EmojiTone = {
	tone: string
	filePath: string
	code: string
}

export type EmojiJson = EmojiJsonBase & {
	skinTones?: EmojiTone[]
}

export type EmojiInfo = {
	/** Emoji code */
	code: string
	/** Emoji namespace */
	ns: string
	/** Emoji suffix */
	suffix?: string
	/** Skin tone */
	tone?: string
	/** Emoji size */
	size?: number
}
