import type { TableProps } from "antd"
import { Table } from "antd"
import type { AnyObject } from "antd/es/_util/type"
import { useDelightfulSpinProps } from "../DelightfulSpin/style"
import { useStyles } from "./style"

export type DelightfulTableProps<D extends AnyObject = AnyObject> = TableProps<D>

function DelightfulTable<D extends AnyObject = AnyObject>({
	loading = false,
	scroll,
	...props
}: DelightfulTableProps<D>) {
	const delightfulSpinProps = useDelightfulSpinProps()

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
