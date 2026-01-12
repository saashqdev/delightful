import DebuggerToolbar from "./Debugger"

export default function useToolbar() {
	return [
		{
			icon: DebuggerToolbar,
			tooltip: "Single point debug",
		},
	]
}

