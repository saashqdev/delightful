import { GroupConversationMember } from "@/types/chat/conversation"
import { GroupInfo } from "@/types/organization"
import { makeAutoObservable } from "mobx"

class GroupInfoStore {
	map: Map<IDBValidKey, GroupInfo> = new Map()

	currentGroup: GroupInfo | undefined

	currentGroupMembers: GroupConversationMember[] = []

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	get(key: string): GroupInfo | undefined {
		return this.map.get(key)
	}

	set(key: string, value: GroupInfo) {
		this.map.set(key, value)
	}

	get currentGroupId() {
		return this.currentGroup?.id
	}

	/**
	 * Set the current group
	 * @param group Group info
	 */
	setCurrentGroup(group: GroupInfo) {
		this.currentGroup = group
	}

	/**
	 * Set current group members
	 * @param members Members
	 */
	setCurrentGroupMembers(members: GroupConversationMember[]) {
		this.currentGroupMembers = members
	}

	/**
	 * Remove group members
	 * @param userIds User IDs
	 */
	removeGroupMembers(userIds: string[]) {
		this.currentGroupMembers = this.currentGroupMembers.filter(
			(member) => !userIds.includes(member.user_id),
		)
	}
}

export default new GroupInfoStore()
