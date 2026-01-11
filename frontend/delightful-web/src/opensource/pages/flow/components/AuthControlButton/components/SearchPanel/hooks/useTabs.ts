import { useMemoizedFn } from "ahooks"
import { useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import { useAuthControl } from "../../../AuthManagerModal/context/AuthControlContext"
import { ManagerModalType } from "../../../AuthManagerModal/types"

export enum AuthSearchTypes {
	// Recent contacts
	Member = 1,
	// Organization structure
	Organization = 2,
	// Group chat
	Group = 3,
	// Partners
	Partner = 4,
}

export default function useTabs() {
	const { t } = useTranslation()

	const { type } = useAuthControl()

	const [tab, setTab] = useState(
		type === ManagerModalType.Auth ? AuthSearchTypes.Member : AuthSearchTypes.Organization,
	)

	const changeTab = useMemoizedFn((e) => {
		if (typeof e !== "object") {
			setTab(e)
			return
		}
		setTab(e.target.value)
	})

	// Current tab list
	const tabList = useMemo(() => {
		return [
			{
				label: t("common.recentContact", { ns: "flow" }),
				value: AuthSearchTypes.Member,
				onClick: () => changeTab(AuthSearchTypes.Member),
			},
			{
				label: t("common.byOrganization", { ns: "flow" }),
				value: AuthSearchTypes.Organization,
				onClick: () => changeTab(AuthSearchTypes.Organization),
			},
			// {
			// 	label: "group chat",
			// 	value: AuthSearchTypes.Group,
			// 	onClick: () => changeTab(AuthSearchTypes.Group),
			// },
			// {
			// 	label: "partners",
			// 	value: AuthSearchTypes.Partner,
			// 	onClick: () => changeTab(AuthSearchTypes.Partner),
			// },
		]
	}, [changeTab, t])

	return {
		tabList,
		tab,
	}
}





