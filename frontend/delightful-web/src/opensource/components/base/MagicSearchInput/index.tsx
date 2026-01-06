import { Input } from "antd"
import { createStyles } from "antd-style"
import type { SearchProps } from "antd/es/input"
import { IconSearch } from "@tabler/icons-react"

import { colorUsages } from "@/opensource/providers/ThemeProvider/colors"
import DelightfulIcon from "../DelightfulIcon"

interface DelightfulSearchInputProps extends SearchProps {}

const useStyles = createStyles(({ token, css, prefixCls }) => ({
	search: css`
		border-radius: 8px;
		border: 1px solid ${token.colorBorderSecondary};
		background-color: ${token.delightfulColorScales.grey[0]};
		overflow: hidden;

		.${prefixCls}-input-group-addon {
			background-color: transparent;
			border: none;
		}
		.${prefixCls}-input-search-button {
			display: none;
		}
		.delightful-input-group-wrapper {
			border-radius: 8px;
		}
		.${prefixCls}-input {
			border: none;
			padding-left: 0;
			background-color: transparent;
			&:focus {
				border: none;
				box-shadow: none;
			}
		}
	`,
}))

function DelightfulSearchInput({ ...rest }: DelightfulSearchInputProps) {
	const { styles } = useStyles()

	return (
		<Input.Search
			size="large"
			addonBefore={
				<DelightfulIcon color={colorUsages.text[3]} stroke={2} component={IconSearch} />
			}
			className={styles.search}
			{...rest}
		/>
	)
}

export default DelightfulSearchInput
