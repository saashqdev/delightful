import type Topic from "@/opensource/models/chat/topic"
import { t } from "i18next"
import { makeAutoObservable } from "mobx"

/**
 * Topic UI state management
 */
class TopicStore {
	/**
	 * Topic list
	 */
	topicList: Topic[] = []

	/**
	 * Whether the topic list is loading
	 */
	isLoading: boolean = false

	constructor() {
		makeAutoObservable(this, {}, { autoBind: true })
	}

	get timeTaskTopicList() {
		return this.topicList.map((topic) => ({
			label: topic.name || t("chat.topic.newTopic", { ns: "interface" }),
			value: topic.id,
		}))
	}

	getTopicName(topicId: string) {
		return this.topicList.find((topic) => topic.id === topicId)?.name
	}

	/**
	 * Set topic list
	 * @param topicList Topic list
	 */
	setTopicList(topicList: Topic[]) {
		this.topicList = topicList
	}

	/**
	 * Set loading state
	 * @param loading Whether loading
	 */
	setLoading(loading: boolean) {
		this.isLoading = loading
	}

	/**
	 * Add topic to the start of the list
	 * @param topic Topic
	 */
	unshiftTopic(topic: Topic) {
		if (this.topicList.find((tt) => tt.id === topic.id)) {
			return
		}
		this.topicList = [topic, ...this.topicList]
	}

	/**
	 * Add topic to the list
	 * @param topic Topic
	 */
	addTopic(topic: Topic) {
		if (this.topicList.find((tt) => tt.id === topic.id)) {
			return
		}
		this.topicList.push(topic)
	}

	/**
	 * Update topic
	 * @param topicId Topic ID
	 * @param updates Updates
	 */
	updateTopic(topicId: string, updates: Partial<Topic>) {
		const index = this.topicList.findIndex((topic) => topic.id === topicId)
		console.log("updateTopic ====== ", index, updates)

		if (index !== -1) {
			// Create a new topic array to trigger MobX reactivity
			const updatedTopicList = [...this.topicList]
			const topic = updatedTopicList[index]

			// Update properties
			Object.entries(updates).forEach(([key, value]) => {
				// @ts-ignore
				topic[key] = value
			})

			// If name is updated, call dedicated method
			if (updates.name) {
				topic.updateName(updates.name)
			}

			// If description is updated, call dedicated method
			if (updates.description) {
				topic.updateDescription(updates.description)
			}

			// Replace the entire array to ensure the view updates
			this.topicList = updatedTopicList
		}
	}

	/**
	 * Replace a specific topic
	 * @param oldTopicId Old topic ID
	 * @param newTopic New topic
	 */
	replaceTopic(oldTopicId: string, newTopic: Topic) {
		const index = this.topicList.findIndex((topic) => topic.id === oldTopicId)
		if (index !== -1) {
			this.topicList[index] = newTopic
		}
	}

	/**
	 * Remove a topic
	 * @param topicId Topic ID
	 */
	removeTopic(topicId: string) {
		this.topicList = this.topicList.filter((topic) => topic.id !== topicId)
	}

	/**
	 * Clear topic list
	 */
	clearTopicList() {
		this.topicList = []
	}

	/**
	 * Get a topic by ID
	 * @param topicId Topic ID
	 * @returns Topic
	 */
	getTopicById(topicId: string): Topic | undefined {
		return this.topicList.find((topic) => topic.id === topicId)
	}
}

export default new TopicStore()
