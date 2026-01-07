import { memo } from "react"
import { useTranslation } from "react-i18next"
import { Flex, Form } from "antd"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { InstructionType } from "@/types/bot"
import { resolveToString } from "@delightful/es6-template-strings"
import type { DelightfulRichEditorRef } from "@/opensource/components/base/DelightfulRichEditor"
import DelightfulRichEditor from "@/opensource/components/base/DelightfulRichEditor"
import type { UseEditorOptions } from "@tiptap/react"
import { IconAt } from "@tabler/icons-react"
import { useStyles } from "../../styles"

interface InstructionContentProps {
	editorRef: React.RefObject<DelightfulRichEditorRef>
	selectedValue: InstructionType
	editorOptions: UseEditorOptions
	insertInstruction: () => void
}
export const InstructionContent = memo(
	({ selectedValue, insertInstruction, editorRef, editorOptions }: InstructionContentProps) => {
		const { t } = useTranslation("interface")
		const { styles, cx } = useStyles()

		return (
			<Flex vertical gap={8}>
				<Flex gap={6} vertical>
					<Flex justify="space-between" align="center">
						<span className={cx(styles.labelText, styles.required)}>
							{t("explore.form.instructionContent")}
						</span>
						{selectedValue !== InstructionType.TEXT && (
							<DelightfulButton
								type="text"
								className={styles.atButton}
								onClick={insertInstruction}
							>
								<IconAt size={14} color="currentColor" />
								{t("explore.form.insertInstruction")}
							</DelightfulButton>
						)}
					</Flex>
					<Form.Item
						name="content"
						required
						rules={[
							{
								required: true,
								message: resolveToString(t("form.required"), {
									label: t("explore.form.instructionContent"),
								}),
							},
						]}
					>
						<DelightfulRichEditor
							ref={editorRef}
							showToolBar={false}
							className={styles.editor}
							placeholder={t("explore.form.instructionContentPlaceholder")}
							editorProps={editorOptions}
						/>
					</Form.Item>
				</Flex>
			</Flex>
		)
	},
)
