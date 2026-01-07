import { createStyles } from "antd-style"

// Create inline styles
export const useStyles = createStyles(({ css }) => ({
	placeholder: css`
		font-size: 1em;
		line-height: 1.5em;
		padding: 0;
		margin: 0;
		pointer-events: none;
		user-select: none;
		font-family: inherit;
		font-weight: normal;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 95%;
		opacity: 0.8; /* Slightly reduce opacity */
		letter-spacing: normal;
		transition: opacity 0.2s;

		/* Slightly fade placeholder when editor is hovered */
		&:hover {
			opacity: 0.6;
		}
	`,
}))
