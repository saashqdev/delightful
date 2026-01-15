import { useCallback, useEffect, useRef } from "react"
import { useTranslation } from "react-i18next"
import { App, Flex } from "antd"
import DelightfulButton from "@/opensource/components/base/DelightfulButton"
import type DelightfulModal from "@/opensource/components/base/DelightfulModal"
import { AppEnv } from "@/types/env"
import useCheckUpdateWorker from "./useCheckUpdateWorker"
import { ReflectMessageType } from "./const"
import { isBreakingVersion } from "./utils"

const useVersion = () => {
	const forbidUpdate = useRef(false)
	const versionRef = useRef<string>()
	const modalRef = useRef<ReturnType<typeof DelightfulModal.info> | null>(null)
	const openedRef = useRef(false)
	const { t } = useTranslation("interface")

	const { modal } = App.useApp()

	const { start, stop, refresh, workerRef } = useCheckUpdateWorker({
		name: "updateModal",
		type: "module",
	})

	// Notify user about an available update
	const openNotification = useCallback(() => {
		openedRef.current = true
		forbidUpdate.current = true
		const config = {
			title: t("FrontendVersionDetected"),
			content: t("PleaseReloadThePage"),
			centered: true,
			maskClosable: false,
			closable: false,
			footer: (
				<Flex justify="flex-end" gap={8}>
					<DelightfulButton
						onClick={() => {
							forbidUpdate.current = false
							modalRef.current?.destroy()
							modalRef.current = null
						}}
					>
						{t("common.cancel")}
					</DelightfulButton>
					<DelightfulButton
						type="primary"
						onClick={() => {
							refresh()
							window.location.reload()
						}}
					>
						{t("common.refresh")}
					</DelightfulButton>
				</Flex>
			),
		}
		if (modalRef.current) {
			modalRef.current.update(config)
		} else {
			modalRef.current = modal.info(config)
		}
	}, [modal, refresh, t])

	// Decide whether to prompt for an update based on version
	const handlePageUpdateCheck = useCallback(
		(latestVersion: string) => {
			try {
				if (latestVersion) {
					const currentVersion = versionRef.current
					if (!currentVersion) {
						versionRef.current = latestVersion
					} else if (currentVersion === latestVersion) {
						console.log("Latest version", latestVersion)
					} else if (
						isBreakingVersion(currentVersion, latestVersion) &&
						!forbidUpdate.current &&
						!openedRef.current
					) {
						openNotification()
					}
				}
			} catch (error) {
				console.error(error)
			}
		},
		[openNotification],
	)

	// Stop polling
	const stopPollingPageUpdate = useCallback(() => {
		stop()
	}, [stop])

	// Start polling
	const startPollingPageUpdate = useCallback(() => {
		// Skip update prompts outside production
		if (window?.CONFIG?.DELIGHTFUL_APP_ENV !== AppEnv.Production) return
		stopPollingPageUpdate()
		// Reset timer
		start()
	}, [start, stopPollingPageUpdate])

	const handleVisibilitychange = useCallback(() => {
		if (document.visibilityState === "visible") {
			startPollingPageUpdate()
		} else {
			stopPollingPageUpdate()
		}
	}, [startPollingPageUpdate, stopPollingPageUpdate])

	useEffect(() => {
		// visibilitychange does not fire on init, so start polling proactively
		startPollingPageUpdate()
		document.addEventListener("visibilitychange", handleVisibilitychange)
		return () => {
			document.removeEventListener("visibilitychange", handleVisibilitychange)
		}
	}, [handleVisibilitychange, startPollingPageUpdate])

	useEffect(() => {
		if (workerRef.current) {
			workerRef.current.port.onmessage = (e) => {
				const data = e.data || {}
				switch (data.type) {
					case ReflectMessageType.REFLECT_GET_LATEST_VERSION:
						handlePageUpdateCheck(data.data)
						break
					case ReflectMessageType.REFLECT_REFRESH:
						// Another tab refreshed manually; sync this tab
						window.location.reload()
						break
					default:
						break
				}
			}
		}
	}, [handlePageUpdateCheck, workerRef])
}

export default useVersion
