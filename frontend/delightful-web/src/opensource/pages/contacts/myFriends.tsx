import { DelightfulList } from "@/opensource/components/DelightfulList"
import { createStyles } from "antd-style"

const useStyles = createStyles(({ css }) => {
	return {
		list: css`
			width: 100%;
		`,
	}
})

function MyFriends() {
	const { styles } = useStyles()
	return <DelightfulList items={[]} className={styles.list} />
}

export default MyFriends
