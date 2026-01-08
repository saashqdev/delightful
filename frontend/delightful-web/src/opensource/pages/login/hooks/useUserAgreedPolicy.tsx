import { useRef, useState } from "react"
import { useMemoizedFn } from "ahooks"
import AgreePolicyCheckbox from "@/opensource/pages/login/components/AgreePolicyCheckbox"
import { App, Flex } from "antd"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import { useTranslation } from "react-i18next"
import { createStyles } from "antd-style"

const useStyles = createStyles(({ css, isDarkMode, prefixCls, token }) => {
	return {
		modal: css`
			.${prefixCls}-modal-content {
				--${prefixCls}-modal-content-bg: ${
			isDarkMode ? token.delightfulColorScales.grey[0] : token.delightfulColorScales.white
		} !important;
			}
		`,
	}
})

export function useUserAgreedPolicy() {
	const { modal } = App.useApp()
	const { t } = useTranslation("login")
	const { styles } = useStyles()

	const modelInstance = useRef<ReturnType<typeof modal.confirm> | null>(null)

	const [agree, setAgree] = useState(false)

	// Before proceeding to the next step, the user needs to agree to the policy
	const triggerUserAgreedPolicy = useMemoizedFn(() => {
		if (agree) {
			return Promise.resolve()
		}

		return new Promise<boolean>((resolve) => {
			if (modelInstance.current) {
				modelInstance.current?.destroy()
				modelInstance.current = null
			}

			const responseFn = (result: boolean) => {
				resolve(result)
				modelInstance.current?.destroy()
				modelInstance.current = null
			}

			const callback = (e: KeyboardEvent) => {
				e.stopPropagation()
				if (!modelInstance.current) {
					document.removeEventListener("keydown", callback)
					return
				}

				if (e.key === "Enter") {
					responseFn(true)
					document.removeEventListener("keydown", callback)
				} else if (e.key === "Escape") {
					responseFn(false)
					document.removeEventListener("keydown", callback)
				}
			}

			document.addEventListener("keydown", callback)

			modelInstance.current = modal.confirm({
				icon: null,
				centered: true,
				content: <AgreePolicyCheckbox />,
				maskClosable: false,
				className: styles.modal,
				closable: false,
				footer: (
					<Flex justify="flex-end" style={{ marginTop: 20 }} gap={12}>
						<DelightfulButton
							variant="solid"
							type="default"
							onClick={() => responseFn(false)}
						>
							{t("button.cancel")}
						</DelightfulButton>
						<DelightfulButton
							type="primary"
							onClick={() => {
								responseFn(true)
							}}
						>
							{t("button.confirm")}
						</DelightfulButton>
					</Flex>
				),
			})
		}).then((result) => {
			setAgree(result)
			return result ? Promise.resolve() : Promise.reject()
		})
	})

	return {
		agree,
		setAgree,
		triggerUserAgreedPolicy,
	}
}
