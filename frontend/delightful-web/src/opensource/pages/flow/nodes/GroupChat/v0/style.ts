import { createStyles } from "antd-style"

const useStyles = createStyles(({ css }) => {
	return {
		groupChat: css`
			.dropdown-card {
				margin: 0 12px;
			}
			.delightful-form-item {
				margin-bottom: 0;
			}
			.delightful-form-item-label {
				padding-bottom: 6px !important;
				& > label {
					font-size: 12px;
					line-height: 16px;
					color: #1c1d2399;
				}
			}
		`,
		formItem: css`
			margin-top: 8px;
		`,
		switchSettings: css`
			margin: 12px;
			height: 20px;
			line-height: 1;
			.delightful-form-item-control-input-content {
				height: 16px;
				display: flex;
				align-items: center;
			}
			.delightful-form-item-control-input {
				height: 16px;
				min-height: 16px;
				display: flex;
				align-items: center;
			}
		`,
	}
})

export default useStyles
