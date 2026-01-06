import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import DelightfulTag from "@/opensource/components/base/DelightfulTag"
import { IconSitemap } from "@tabler/icons-react"
import type { TagProps } from "antd"
import { Flex } from "antd"
import { createStyles } from "antd-style"
import { memo } from "react"
import { isDepartment, isGroup, isMember } from "../../../OrganizationPanel/utils"
import type { OrganizationSelectItem } from "../../types"

const useStyles = createStyles(({ css, token }) => ({
	tag: css`
		height: min-content;
	`,
	departmentIcon: css`
		color: white;
		border-radius: 4.5px;
		padding: 3px;
		background: ${token.delightfulColorScales.brand[5]};
	`,
}))

const SelectItemTag = memo(function SelectItemTag({
	data,
	onClose,
	...props
}: TagProps & { data: OrganizationSelectItem }) {
	const { styles } = useStyles()

	return (
		<DelightfulTag closable onClose={onClose} className={styles.tag} {...props}>
			{(() => {
				switch (true) {
					case isMember(data):
						return (
							<Flex align="center" gap={10}>
								<DelightfulAvatar size={20} src={data.avatar_url}>
									{data.real_name}
								</DelightfulAvatar>
								{data.real_name}
							</Flex>
						)
					case isDepartment(data):
						return (
							<Flex align="center" gap={10}>
								<DelightfulIcon
									color="currentColor"
									component={IconSitemap}
									size={18}
									className={styles.departmentIcon}
								/>
								{data.name}
							</Flex>
						)
					case isGroup(data):
						return (
							<Flex align="center" gap={10}>
								<DelightfulAvatar size={20} src={data.group_avatar} />
								{data.group_name}
							</Flex>
						)
					default:
						return null
				}
			})()}
		</DelightfulTag>
	)
})

export default SelectItemTag
