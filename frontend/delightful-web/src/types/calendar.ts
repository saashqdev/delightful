import type {
	CalendarStatus,
	ChangeScope,
	EditScope,
} from "@/opensource/pages/calendar/components/CalendarMain/components/NewEventConfigure/constant"
import type { EventRefiners } from "@fullcalendar/common"

export interface CalendarEvent extends EventRefiners {}

export namespace Calendar {
	// Event list
	export type ListItem = {
		id: string
		title: string
		date: string
		is_all_day: boolean
		start_time: string
		end_time: string
		time_slot: string
		progress: string
		status: string
		meeting_room: string | null
	}

	// User free/busy status
	export type UserStatus = {
		id: string
		schedule_id: string
		user_id: string
		status: string
		role: string
		join_time: string
		start_time: string
		end_time: string
		organization_code: string
		create_at: string
		update_at: string
		activity_status: string
	}

	// Comments/activity list
	export type Comments = {
		id: string
		type: number
		resource_id: string
		resource_type: number
		parent_id: string
		description: string
		message: string[]
		attachments: Attachments[]
		organization_code: string
		created_at: string
		updated_at: string
		creator: {
			id: string
			delightful_id: string
			user_id: string
			organization_code: string
			description: string
			like_num: number
			label: string
			status: number
			avatar_url: string
			nickname: string
			extra: string
			created_at: string
			updated_at: string
			deleted_at: string
			user_type: number
		}
	}

	// Reminder rule
	export type ReminderRule = {
		type: string
		offset: string
		remark: string
		custome_time?: string
	}

	// Repeat rule
	export type RepeatRule = {
		repeat_type: string
		interval: number
		termination_type: string
		termination_value?: string
		weekdays: number[]
		monthly_day?: number
	}

	// Attachments
	export type Attachments = {
		file_key: string
		file_name: string
		file_extension: string
		file_path?: string
	}

	// Save event
	export type SaveCalendarParams = {
		id?: string
		// 1: current, 2: all events, 3: all following
		type?: EditScope | ChangeScope
		title: string
		description?: string
		start_time: string
		end_time: string
		is_all_day: boolean
		participant_ids: string[]
		meeting_room_id?: string
		activity_status: string
		location?: {
			title?: string
			desc?: string
		}
		reminder_rule: ReminderRule[]
		repeat_rule: RepeatRule
		attachments: Attachments[]
	}

	// AI event
	export type SaveAiCalendarParams = {
		user_input: string
	}

	// Participant
	export type Participant = {
		avatar_url: string
		nickname: string
		user_id: string
	}

	// Event details
	export type CalendarDetail = SaveCalendarParams & {
		id: string
		series_id: string
		progress: string
		timezone: string
		organizer: {
			user_id: string
			nickname: string
			avatar_url: string
		}
		group_id: string
		summary: string
		comments: Comments[]
		meeting_room: string | null
		meeting_room_name: string
		organization_code: string
		created_at: string
		updated_at: string
		participant: Participant[]
		type?: number
		status: CalendarStatus
	}

	// Get user free/busy status
	export type GetUserStatusParams = {
		start_time: string
		end_time: string
		user_ids: string[]
	}

	// Get event by date
	export type GetCalendarByDayParams = {
		dates: string[]
	}

	// Update event invitation status
	export type UpdateCalendarStatusParams = {
		// Event id
		schedule_id: string
		// Event status
		type: CalendarStatus
		// Modification scope
		modification_type: ChangeScope
	}

	// Comment on event
	export type CommentCalendarParams = {
		schedule_id: string
		message: string
		attachments: Attachments[]
	}

	export type Floors = {
		id: string
		name: string
	}

	export enum LocationType {
		City = "city",
		Location = "location",
		Floor = "floor",
	}

	export enum Capacity {
		Unlimited = "unlimited",
		"0-2" = "0-2",
		"3-6" = "3-6",
		"7-10" = "7-10",
		"10-15" = "10-15",
		"15+" = "15+",
	}

	// Get meeting room filtering criteria
	export type FilteringCriteria = {
		location: {
			id: string
			name: string
			buildings: {
				id: string
				name: string
				floors: Floors[]
			}[]
			floors: Floors[]
		}[]
		capacity: Capacity
	}

	export type Location = {
		location_type: string
		location_value: string
	}

	// Available time slots
	export type AvailabelTimeLine = {
		start_time: string
		end_time: string
		time_slots: string
	}

	// Meeting room list
	export type MeetingRoom = {
		title: MeetingRoom
		id: string
		name: string
		capacity: string
		is_approval: boolean
		labels: string[]
		location: Location
		date: string
		is_allow_all_day: boolean
		available_slots: AvailabelTimeLine[]
		all_day_time_slots: AvailabelTimeLine
		advance_booking_days: number
		allow_periodic_booking: boolean
		booking_slots: {
			schedule_id: string
			user_id: string
			start_time: string
			end_time: string
			time_slots: string
			nickname: string
			is_current_user_booking: boolean
		}[]
	}

	// Available meeting rooms for time period
	export type TimeAvailableParams = {
		start_time?: string
		end_time?: string
		meeting_room_name?: string
		city?: string
		repeat_rule?: {
			repeat_type: string
			interval: number
			termination_type: string
			start_date: string
		}
		location?: Location
	}

	// Get meeting room parameters
	export type MeetingRoomParams = {
		date?: string
		duration?: number | null
		capacity?: string
		meeting_room_name?: string
		location?: Location
	}

	// Delete event parameters
	export type DeleteCalendarParams = {
		schedule_id: string
		type: ChangeScope
	}

	// Add participant parameters
	export type AddParticipantParams = {
		id: string
		participant_ids: string[]
		// 1: add to current event, 2: add to all events
		type: number
	}

	// Transfer organizer parameters
	export type TransferOrganizerParams = {
		schedule_id: string
		target_user_id: string
		is_original_organizer_removed: boolean
	}

	// Filter option parameters
	export type Option = {
		value: string | number
		label: string
		type?: LocationType
		children?: Option[]
	}
}
