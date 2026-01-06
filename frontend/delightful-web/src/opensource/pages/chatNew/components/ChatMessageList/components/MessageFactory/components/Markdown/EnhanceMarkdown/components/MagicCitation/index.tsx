import { Flex, Popover } from "antd"
import Divider from "@/opensource/components/other/Divider"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { useTranslation } from "react-i18next"
import { IconLink } from "@tabler/icons-react"
import DelightfulIcon from "@/opensource/components/base/DelightfulIcon"
import { useMemoizedFn } from "ahooks"
import { useFontSize } from "@/opensource/providers/AppearanceProvider/hooks"
import { useDelightfulCitationSources } from "./hooks"
import { useStyles } from "./style"

const DelightfulCitation = ({ index }: { index: number }) => {
	const { fontSize } = useFontSize()
	const { styles } = useStyles({ fontSize })
	const { sources } = useDelightfulCitationSources()
	const { t } = useTranslation("interface")

	const source = sources[index - 1]

	const openSourcePage = useMemoizedFn(() => {
		if (source) window.open(source.url, "_blank")
	})

	if (!source) return null

	return (
		<Popover
			overlayClassName={styles.relatedPopover}
			title={
				<a href={source.url} className={styles.sourceName} target="_blank" rel="noreferrer">
					{source.name}
				</a>
			}
			content={
				<Flex vertical gap={10}>
					{source.snippet}
					<Divider direction="horizontal" />
					<DelightfulButton
						type="link"
						onClick={openSourcePage}
						size="small"
						className={styles.sourceButton}
					>
						<Flex gap={4} align="center" style={{ width: "100%" }}>
							<DelightfulIcon color="currentColor" component={IconLink} size={14} />
							{t("chat.markdown.citation.viewSource")}
						</Flex>
					</DelightfulButton>
				</Flex>
			}
		>
			<sup className={styles.sourceDot}>{index}</sup>
		</Popover>
	)
}

export default DelightfulCitation
