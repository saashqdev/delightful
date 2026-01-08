import { genRequestUrl } from "@/utils/http"
import type { Calendar } from "@/types/calendar"
import { RequestUrl } from "../constant"
import type { HttpClient } from "../core/HttpClient"

export const generateCalendarApi = (fetch: HttpClient) => ({
	/** Create schedule */
	createCalendar(params: Calendar.SaveCalendarParams) {
		return fetch.post<{ id: string }>(genRequestUrl(RequestUrl.createCalendar), params)
	},

	/** Create AI schedule */
	createAiCalendar(params: Calendar.SaveAiCalendarParams) {
		return fetch.post<Calendar.CalendarDetail>(
			genRequestUrl(RequestUrl.createAiCalendar),
			params,
		)
	},

	/** Get schedule details */
	getCalendarDetail(id: string) {
		return fetch.get<Calendar.CalendarDetail>(
			genRequestUrl(RequestUrl.getCalendarDetail, { id }, { schedule_id: id }),
		)
	},

	/** Update schedule */
	updateCalendar(params: Calendar.SaveCalendarParams) {
		return fetch.put<{ id: string }>(genRequestUrl(RequestUrl.updateCalendar), params)
	},

	/** Delete schedule */
	deleteCalendar(params: Calendar.DeleteCalendarParams) {
		return fetch.delete<null>(
			genRequestUrl(RequestUrl.deleteCalendar, { id: params.schedule_id }),
		)
	},

	/** Get schedules for specified day */
	getCalendar(params: Calendar.GetCalendarByDayParams) {
		return fetch.post<Calendar.ListItem[]>(genRequestUrl(RequestUrl.getCalendarByDay), params)
	},

	/** User busy/free status */
	getUserStatus(params: Calendar.GetUserStatusParams) {
		return fetch.get<Calendar.UserStatus[]>(genRequestUrl(RequestUrl.getUserStatus, {}, params))
	},

	/** Comment on schedule */
	commentCalendar(params: Calendar.CommentCalendarParams) {
		return fetch.post<Calendar.Comments>(
			genRequestUrl(RequestUrl.commentCalendar, { id: params.schedule_id }),
			params,
		)
	},

	/** Add participant */
	addParticipant(params: Calendar.AddParticipantParams) {
		return fetch.post<null>(genRequestUrl(RequestUrl.addParticipant, { id: params.id }), params)
	},

	/** Transfer schedule organizer */
	transferOrganizer(params: Calendar.TransferOrganizerParams) {
		return fetch.put<null>(
			genRequestUrl(RequestUrl.transferOrganizer, { id: params.schedule_id }),
			params,
		)
	},

	/** Get meeting rooms */
	getMeetingRoom(params: Calendar.MeetingRoomParams) {
		return fetch.post<Calendar.MeetingRoom[]>(genRequestUrl(RequestUrl.getMeetingRooms), params)
	},

	/** Get meeting room filter criteria */
	getFilteringCriteria() {
		return fetch.get<Calendar.FilteringCriteria>(genRequestUrl(RequestUrl.getFilteringCriteria))
	},

	/** Get available meeting rooms for specified time period */
	getAvailableMeetingRoom(params: Calendar.TimeAvailableParams) {
		return fetch.get<Calendar.MeetingRoom[]>(
			genRequestUrl(RequestUrl.getAvaliableMeetingRooms, {}, params),
		)
	},

	/** Get/create schedule summary */
	getSummary(id: string) {
		return fetch.get<string>(genRequestUrl(RequestUrl.getCalendarSummary, { id }))
	},

	/** Update schedule invitation status */
	updateCalendarStatus(params: Calendar.UpdateCalendarStatusParams) {
		return fetch.put<null>(
			genRequestUrl(RequestUrl.updateCalendarStatus, { id: params.schedule_id }),
			params,
		)
	},

	/** Create group chat */
	createCalendarGroup(params: { schedule_id: string }) {
		return fetch.post<{ group_id: string }>(
			genRequestUrl(RequestUrl.createCalendarGroup, { id: params.schedule_id }),
			params,
		)
	},
})
