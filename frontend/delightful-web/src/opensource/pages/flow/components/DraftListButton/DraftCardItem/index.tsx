import type { FlowDraft } from "@/types/flow"
import { IconEdit, IconTrashX } from "@tabler/icons-react"
import { Flex, Popconfirm, Tooltip } from "antd"
import EmptyIcon from "@/assets/logos/empty.svg"
import type { DelightfulFlow } from "@delightful/delightful-flow/dist/DelightfulFlow/types/flow"
import { useMemo, type MutableRefObject } from "react"
import type { DelightfulFlowInstance } from "@delightful/delightful-flow/dist/DelightfulFlow"
import { cx } from "antd-style"
import DelightfulAvatar from "@/opensource/components/base/DelightfulAvatar"
import { useTranslation } from "react-i18next"
import { resolveToString } from "@delightful/es6-template-strings"
import styles from "./index.module.less"
import SaveDraftButton from "../../SaveDraftButton"

type DraftCardItemProps = {
	draft: FlowDraft.ListItem
	onDeleteItem: (draftId: string) => void
	flow?: DelightfulFlow.Flow
	flowInstance?: MutableRefObject<DelightfulFlowInstance | null>
	onSwitchDraft: (draft: FlowDraft.ListItem) => void
	initDraftList?: (this: any, flowCode: any) => Promise<FlowDraft.Detail[]>
}

export default function DraftCardItem({
	draft,
	onDeleteItem,
	flow,
	flowInstance,
	onSwitchDraft,
	initDraftList,
}: DraftCardItemProps) {
	const { t } = useTranslation()

	const isActiveDraft = useMemo(() => {
		// @ts-ignore
		return flow?.draft_id === draft.id
		// @ts-ignore
	}, [draft.id, flow?.draft_id])

	return (
		<div
			className={cx(styles.draftCardItem, {
				[styles.active]: isActiveDraft,
			})}
			onClick={() => onSwitchDraft(draft)}
		>
			<Flex className={styles.header} justify="space-between">
				<Flex>
					<Tooltip title={draft.name}>
						<span className={styles.draftName}>
							{draft.name}
							{isActiveDraft ? `(${t("common.currentDraft", { ns: "flow" })})` : ""}
						</span>
					</Tooltip>
				</Flex>
				<Flex gap={8} onClick={(e) => e.stopPropagation()}>
					<Tooltip title={t("common.updateDraft", { ns: "flow" })}>
						<SaveDraftButton
							draft={draft}
							flowInstance={flowInstance}
							flow={flow}
							Icon={(props: Record<string, any>) => {
								return (
									<div className={styles.iconWrap} {...props}>
										<IconEdit height={18} />
									</div>
								)
							}}
							initDraftList={initDraftList}
						/>
					</Tooltip>
					<Tooltip title={t("common.deleteDraft", { ns: "flow" })}>
						<Popconfirm
							title={t("common.confirmDelete", { ns: "flow" })}
							onConfirm={() => {
								onDeleteItem(draft.id)
							}}
						>
							<div className={styles.iconWrap} onClick={(e) => e.stopPropagation()}>
								<IconTrashX height={18} />
							</div>
						</Popconfirm>
					</Tooltip>
				</Flex>
			</Flex>
			<span className={styles.draftDesc}>{draft.description}</span>
			<Flex justify="space-between">
				<Flex justify="space-between" gap={4} align="center">
					<DelightfulAvatar
						size={20}
						shape="circle"
						src={draft?.modifier_info?.avatar || EmptyIcon}
					/>
					<span className={styles.name}>{draft?.modifier_info?.name || "Unknown user"}</span>
				</Flex>
				<Flex>
					<span>
						{resolveToString(t("common.draftUpdateAt", { ns: "flow" }), {
							timeStr: draft.updated_at,
						})}
					</span>
				</Flex>
			</Flex>
		</div>
	)
}





