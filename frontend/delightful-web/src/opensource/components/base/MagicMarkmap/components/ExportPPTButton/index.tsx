import type { DelightfulButtonProps } from "@/opensource/components/base/DelightfulButton"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import DelightfulMarpit from "@/opensource/components/base/DelightfulMarpit"
import DelightfulModal from "@/opensource/components/base/DelightfulModal"
import IconWandColorful from "@/enhance/tabler/icons-react/icons/IconWandColorful"
import { memo } from "react"
import { useTranslation } from "react-i18next"
import { useBoolean } from "ahooks"
import { useStyles } from "./styles"

const ExportPPTButton = memo(({ content, className, ...rest }: DelightfulButtonProps) => {
	const { t } = useTranslation("interface")
	const { styles, cx } = useStyles()

	const [open, { setTrue, setFalse }] = useBoolean(false)

	if (!content) return null

	return (
		<>
			<DelightfulButton
				type="text"
				className={cx(styles.button, className)}
				icon={<IconWandColorful />}
				onClick={setTrue}
				{...rest}
			>
				<span className={styles.text}>{t("chat.markmap.generatePPT")}</span>
			</DelightfulButton>
			<DelightfulModal
				open={open}
				width="fit-content"
				destroyOnClose
				classNames={{
					body: styles.modalBody,
				}}
				title={t("chat.markmap.generatePPT")}
				onCancel={setFalse}
				closable
				footer={null}
			>
				<DelightfulMarpit content={content} />
			</DelightfulModal>
		</>
	)
})

export default ExportPPTButton
