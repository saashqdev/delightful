import type { TableProps } from "antd"
import { Table } from "antd"
import { createStyles } from "antd-style"
import type { AnyObject } from "antd/es/_util/type"
import { useDelightfulSpinProps } from "../DelightfulSpin/useDelightfulSpinProps"

const useStyles = createStyles(
	(
		{ css, prefixCls },
		{ scrollHeight, isLoading }: { scrollHeight?: number | string; isLoading?: boolean },
	) => {
		return {
			table: css`
				width: 100%;
				.${prefixCls}-table-body {
					overflow-y: auto !important;
				}

				.${prefixCls}-table-row {
					cursor: pointer;
				}

				.${prefixCls}-table-placeholder {
					height: ${scrollHeight ?? "unset"};
				}

				.${prefixCls}-empty {
					visibility: ${isLoading ? "hidden" : "visible"};
				}
			`,
		}
	},
)

function DelightfulTable<D extends AnyObject = AnyObject>({
	loading = false,
	scroll,
	...props
}: TableProps<D>) {
	const delightfulSpinProps = useDelightfulSpinProps(true)

	const { styles } = useStyles({
		scrollHeight: scroll?.y,
		isLoading: typeof loading === "boolean" ? loading : loading?.spinning,
	})

	return (
		<Table<D>
			className={styles.table}
			loading={
				typeof loading === "object" ? loading : { spinning: loading, ...delightfulSpinProps }
			}
			scroll={{ x: "max-content", ...scroll }}
			{...props}
		/>
	)
}

export default DelightfulTable
