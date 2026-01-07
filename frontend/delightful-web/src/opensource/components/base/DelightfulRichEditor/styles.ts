import { createStyles } from "antd-style"

const useStyles = createStyles(({ css, token }) => {
	return {
		toolbar: css`
			border-bottom: 1px solid ${token.colorBorderSecondary};
		`,
		content: css`
			outline: none;
			height: auto;
			overflow: hidden;

			/* Fix height issues */
			display: flex;
			flex-direction: column;

			/* Ensure the editor content area has no outline and correct height */
			.ProseMirror {
				outline: none !important;
				box-shadow: none !important;
				border: none !important;
				flex: 1;
				overflow: hidden;
				height: auto !important;
				min-height: inherit;
				max-height: none;
				caret-color: ${token.colorText}; /* Set caret color */
			}

			/* Ensure paragraphs have no extra margins */
			.ProseMirror p {
				margin: 0;
				padding: 0;
				line-height: 1.5em; /* Set fixed line height */
				min-height: 1em;
				position: relative;
			}

			/* Cursor style that doesn't affect layout */
			.ProseMirror .ProseMirror-cursor {
				margin: 0;
				padding: 0;
				position: absolute;
				z-index: 1;
			}

			/* Ensure the editor content area has no outline when focused */
			.ProseMirror:focus,
			.ProseMirror:focus-visible {
				outline: none !important;
				box-shadow: none !important;
				border: none !important;
			}

			/* Ensure the contenteditable area has no outline */
			div[contenteditable="true"] {
				outline: none !important;
				box-shadow: none !important;
				border: none !important;
				height: auto;
				max-height: none;
			}

			/* Ensure the contenteditable area has no outline when focused */
			div[contenteditable="true"]:focus,
			div[contenteditable="true"]:focus-visible {
				outline: none !important;
				box-shadow: none !important;
				border: none !important;
			}

			/* Restore placeholder styles */
			.ProseMirror p.is-editor-empty:first-child::before,
			.ProseMirror p.is-empty::before {
				color: ${token.colorTextPlaceholder};
				content: attr(data-placeholder);
				height: 0;
				pointer-events: none;
				position: absolute;
				top: 0;
				left: 0;
				transition: none;
				transform: translateY(0);
				z-index: 0;
				line-height: 1.5em; /* Match paragraph line height */
				padding: 0;
				margin: 0;
			}

			/* Placeholder styles when focused */
			.ProseMirror:focus p.is-editor-empty:first-child::before,
			.ProseMirror:focus p.is-empty::before {
				color: ${token.colorTextQuaternary};
				transform: translateY(0);
			}

			/* Restore autocomplete suggestion styles */
			p[data-suggestion]::after {
				color: ${token.colorTextQuaternary};
				content: attr(data-suggestion);
				pointer-events: none;
				height: 0;
			}
		`,
		mention: css`
			color: ${token.colorPrimary};
		`,
		emoji: css`
			display: inline-block;
			vertical-align: middle;
			width: 1.2em;
			height: 1.2em;
			transform: translateY(-0.7px);
		`,
		error: css`
			color: ${token.colorError};
			margin-top: 8px;
			font-size: 14px;
		`,
	}
})

export default useStyles
