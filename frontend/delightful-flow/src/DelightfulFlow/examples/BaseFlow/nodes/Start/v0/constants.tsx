export enum TriggerType {
	// 发送新message时
	Message = 1,
	// 聊天window打开时
	NewChat = 2,
	// 定时触发
	TimeTrigger = 3,
	// parameter调用
	Arguments = 4,
	// loop起始
	LoopStart = 5,
}

