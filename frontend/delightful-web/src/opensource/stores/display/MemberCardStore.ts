/* eslint-disable class-methods-use-this */
import { DomClassName } from "@/const/dom"
import userInfoStore from "@/opensource/stores/userInfo"
import type { StructureUserItem } from "@/types/organization"
import { makeAutoObservable } from "mobx"
import userInfoService from "@/opensource/services/userInfo"

/**
 * Member card
 */
class MemberCardStore {
	/**
	 * Hover state
	 */
	isHover = false

	/**
	 * Open state
	 */
	open = false

	/**
	 * Card dimensions
	 */
	size: { width: number; height: number } = { width: 320, height: 500 }

	/**
	 * Card margin
	 */
	marginSize: number = 10

	/**
	 * Card position
	 */
	position: { x: number; y: number } = { x: 0, y: 0 }

	/**
	 * User ID
	 */
	uid: string | null = null

	/**
	 * User info
	 */
	userInfo: StructureUserItem | undefined = undefined

	/**
	 * Card class name
	 */
	domClassName = DomClassName.MEMBER_CARD

	/**
	 * Card animation duration
	 */
	animationDuration = 100

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	/**
	 * Set user info
	 * @param userInfo User info
	 */
	setUserInfo(userInfo: StructureUserItem | undefined) {
		this.userInfo = userInfo
	}

	/**
	 * Open the card
	 * @param uid User ID
	 * @param position Card position
	 */
	openCard(uid: string, position: { x: number; y: number }) {
		this.open = true
		this.setPosition(position)
		this.uid = uid

		// Adjust position
		this.adjustPosition()

		// Read cached user info
		this.setUserInfo(userInfoStore.get(uid))

		// Refresh user info
		userInfoService.fetchUserInfos([uid], 2).then(() => {
			this.setUserInfo(userInfoStore.get(uid))
		})
	}

	/**
	 * Close the card
	 */
	closeCard(force = false) {
		if (this.isHover && !force) return
		this.open = false
		this.isHover = false
		// Reset position after animation completes
		setTimeout(() => {
			this.position = { x: 0, y: 0 }
			this.uid = null
			this.setUserInfo(undefined)
		}, this.animationDuration)
	}

	/**
	 * Set card position
	 * @param position Card position
	 */
	setPosition(position: { x: number; y: number }) {
		this.position.x = position.x + 10
		this.position.y = position.y + 10
		this.adjustPosition()
	}

	/**
	 * Set card size
	 * @param size Card size
	 */
	setSize(size: { width: number; height: number }) {
		this.size = size
		// Adjust position
		this.adjustPosition()
	}

	/**
	 * Adjust card position
	 */
	adjustPosition() {
		// Adjust to prevent overflow off-screen
		if (typeof window !== "undefined") {
			const windowWidth = window.innerWidth - this.marginSize * 2
			const windowHeight = window.innerHeight - this.marginSize * 2

			// Keep right edge within the viewport
			if (this.position.x + this.size.width + this.marginSize > windowWidth) {
				this.position.x = windowWidth - this.size.width - this.marginSize
			}

			// Keep left edge within the viewport
			if (this.position.x < 0) {
				this.position.x = this.marginSize
			}

			// Keep bottom edge within the viewport
			if (this.position.y + this.size.height > windowHeight) {
				this.position.y = windowHeight - this.size.height - this.marginSize
			}

			// Keep top edge within the viewport
			if (this.position.y < 0) {
				this.position.y = this.marginSize
			}
		}
	}

	/**
	 * Extract user ID from an element
	 * @param element Element
	 * @returns User ID
	 */
	getUidFromElement(element: HTMLElement) {
		return element.getAttribute("data-user-id")
	}

	/**
	 * Set hover state
	 * @param isHover Hover flag
	 */
	setIsHover(isHover: boolean) {
		this.isHover = isHover
	}
}

export default new MemberCardStore()
