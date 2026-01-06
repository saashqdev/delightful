import { delightful } from "@/enhance/delightfulElectron"
import { useEffect } from "react"

interface DelightfulElectronViewHookProps {
	onShow?: () => void
	onHide?: () => void
}

export default function useView(props: DelightfulElectronViewHookProps) {
	const { onShow, onHide } = props

	useEffect(() => {
		const unSubscribe = delightful?.view?.onShow?.(() => {
			onShow?.()
		})
		window.addEventListener("beforeunload", unSubscribe)
		return () => {
			window.removeEventListener("beforeunload", unSubscribe)
			unSubscribe?.()
		}
	}, [onShow])

	useEffect(() => {
		const unSubscribe = delightful?.view?.onHide?.(() => {
			onHide?.()
		})
		window.addEventListener("beforeunload", unSubscribe)
		return () => {
			window.removeEventListener("beforeunload", unSubscribe)
			unSubscribe?.()
		}
	}, [onHide])
}
