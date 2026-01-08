import { createStyles } from "antd-style"

export const useVectorKnowledgeSubSiderStyles = createStyles(({ css, isDarkMode, token }) => {
	return {
		container: css`
			width: 100%;
			padding: 0 16px;
		`,
		info: css`
			padding: 16px 0 10px;
			margin-bottom: 4px;
			border-bottom: 1px solid
				${isDarkMode ? token.delightfulColorScales.grey[8] : token.delightfulColorUsages.border};
		`,
		name: css`
			font-weight: 600;
			font-size: 15px;
		`,
		logoImg: css`
			width: 32px;
			height: 32px;
			border-radius: 6px;
		`,
		descLabel: css`
			margin: 12px 0 8px;
			font-size: 12px;
			color: rgba(28, 29, 35, 0.35);
		`,
		descContent: css`
			font-size: 13px;
			color: rgba(28, 29, 35, 0.6);
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 3; /* Limit to maximum 3 lines */
			-webkit-box-orient: vertical;
		`,
		operationBtn: css`
			flex: 1;
		`,
		menu: css`
			border: none !important;

			li {
				width: 100% !important;
				margin-left: 0 !important;
				margin-right: 0 !important;
			}
		`,
		menuItem: css`
			color: #000;
		`,
	}
})
