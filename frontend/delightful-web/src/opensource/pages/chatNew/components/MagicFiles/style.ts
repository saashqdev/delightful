import { calculateRelativeSize } from "@/utils/styles"
import { createStyles } from "antd-style"

export const useStyles = createStyles(
	({ css, token, isDarkMode }, { fontSize }: { fontSize: number }) => {
		return {
			container: css`
				cursor: pointer;
				display: flex;
				width: fit-content;
				max-width: 340px;
				min-width: 200px;
				flex-direction: column;
				justify-content: center;
				align-items: flex-start;
				overflow: hidden;
				border-radius: 12px;
				border: 1px solid ${token.colorBorder};
				background: ${isDarkMode
					? token.delightfulColorScales.grey[1]
					: token.delightfulColorScales.white};
				user-select: none;
			`,
			image: css`
				border: 1px solid ${token.delightfulColorUsages.border};
				border-radius: 6px;
				overflow: hidden;
				width: fit-content;
				user-select: none;
				max-width: 200px;
			`,
			top: css`
				padding: 10px 14px;
			`,
			name: css`
				color: ${token.delightfulColorUsages.text[1]};
				text-align: justify;
				font-weight: 400;
				font-size: ${fontSize}px;
				line-height: ${calculateRelativeSize(20, fontSize)}px;
			`,
			size: css`
				color: ${token.delightfulColorUsages.text[3]};
				font-weight: 400;
				font-size: ${calculateRelativeSize(12, fontSize)}px;
				line-height: ${calculateRelativeSize(16, fontSize)}px;
			`,
			footer: css`
				width: 100%;
				border-top: 1px solid ${token.delightfulColorUsages.border};
				background: ${isDarkMode
					? token.delightfulColorScales.grey[1]
					: token.delightfulColorScales.white};

				> button:not(:last-child) {
					border-right: 1px solid ${token.delightfulColorUsages.border};
				}
			`,
			button: css`
				border-radius: 0;
				color: ${token.delightfulColorUsages.text[1]};
				text-align: justify;

				font-weight: 400;
				font-size: ${calculateRelativeSize(12, fontSize)}px;
				line-height: ${calculateRelativeSize(16, fontSize)}px;
			`,
		}
	},
)
