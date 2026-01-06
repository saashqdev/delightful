import { makeAutoObservable } from "mobx"

class AiCompletionTip {
	/** Whether to show */
	visible: boolean = false
	/** Position */
	position: { top: number; left: number } = { top: -100, left: -100 }

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	tip: string = ""

	/**
	 * Show
	 * @param position Position
	 */
	show(position: { top: number; left: number }) {
		this.position = position
		this.visible = true
	}

	/**
	 * Hide
	 */
	hide() {
		this.visible = false
		this.position = { top: -100, left: -100 }
	}
}

export default new AiCompletionTip()
