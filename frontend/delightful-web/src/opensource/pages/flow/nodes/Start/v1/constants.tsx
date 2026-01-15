export enum TriggerType {
	// When a new message is sent
	Message = 1,
	// When chat window opens
	NewChat = 2,
	// Time trigger
	TimeTrigger = 3,
	// Parameter invocation
	Arguments = 4,
	// Loop start
	LoopStart = 5,
	// When added as friend
	NewFriends = 7,
}

// Trigger type options
export const TriggerTypeOptions = [
	{
		label: "When a new message is sent",
		value: TriggerType.Message,
	},
	{
		label: "When chat window opens",
		value: TriggerType.NewChat,
	},
]

export default {}
