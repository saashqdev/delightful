import { describe, expect, it, vi, beforeEach, afterEach } from "vitest"
import { getLatestAppVersion } from "../utils"
import * as worker from "../worker"

// Mock the utils module
vi.mock("../utils", () => ({
	generateUUID: vi.fn().mockReturnValue("test-uuid"),
	getLatestAppVersion: vi.fn().mockResolvedValue("1.0.0"),
}))

// Define MessagePort type
type MockMessagePort = {
	id: string
	postMessage: ReturnType<typeof vi.fn>
	onmessage: ((event: { data: any }) => void) | null
}

describe("version-check worker", () => {
	// Mock port object
	const createMockPort = (): MockMessagePort => ({
		id: "",
		postMessage: vi.fn(),
		onmessage: null,
	})

	// Mock timers
	beforeEach(() => {
		vi.useFakeTimers()
		// @ts-ignore
		global.self = { location: { origin: "https://example.com" } }
	})

	afterEach(() => {
		vi.clearAllMocks()
		vi.clearAllTimers()
		vi.useRealTimers()
		// Reset worker state
		worker.portList.length = 0
		worker.visiblePorts.length = 0
		if (worker.state.intervalId) {
			clearInterval(worker.state.intervalId)
			worker.state.intervalId = null
		}
	})

	describe("port connection", () => {
		it("should handle new port connection", () => {
			const mockPort = createMockPort()
			const event = { ports: [mockPort] }

			// Trigger connection event
			worker.onconnect(event)

			// Verify the port is correctly initialized
			expect(mockPort.id).toBe("test-uuid")
			expect(mockPort.onmessage).toBeDefined()
		})
	})

	describe("message handling", () => {
		it("should handle 'start' message and start polling", async () => {
			const mockPort = createMockPort()
			const event = { ports: [mockPort] }
			worker.onconnect(event)

			// Simulate start message
			await mockPort.onmessage?.({ data: { type: "start" } })

			// Verify initial version check
			expect(getLatestAppVersion).toHaveBeenCalled()
			expect(mockPort.postMessage).toHaveBeenCalledWith({
				type: "reflectGetLatestVersion",
				data: "1.0.0",
			})

			// Verify the timer is set
			await vi.advanceTimersByTimeAsync(30000)
			expect(getLatestAppVersion).toHaveBeenCalledTimes(2)
		})

		it("should handle 'stop' message and stop polling when all ports are invisible", async () => {
			const mockPort = createMockPort()
			const event = { ports: [mockPort] }
			worker.onconnect(event)

			// Send start message first
			await mockPort.onmessage?.({ data: { type: "start" } })

			// Then send stop message
			await mockPort.onmessage?.({ data: { type: "stop" } })

			// Verify the timer is cleared
			await vi.advanceTimersByTimeAsync(30000)
			expect(getLatestAppVersion).toHaveBeenCalledTimes(1)
		})

		it("should handle 'close' message and remove port", () => {
			const mockPort = createMockPort()
			const event = { ports: [mockPort] }
			worker.onconnect(event)

			// Send close message
			mockPort.onmessage?.({ data: { type: "close" } })

			// Verify the port is removed
			expect(worker.portList).toHaveLength(0)
		})

		it("should handle 'refresh' message and notify other ports", async () => {
			const mockPort1 = createMockPort()
			const mockPort2 = createMockPort()

			// Connect two ports
			worker.onconnect({ ports: [mockPort1] })
			worker.onconnect({ ports: [mockPort2] })

			// Wait for async operations to complete
			await Promise.resolve()
			await vi.advanceTimersByTimeAsync(0)

			// Verify ports are correctly added
			expect(worker.portList).toHaveLength(2)
			expect(worker.portList[0].id).toBe("test-uuid")
			expect(worker.portList[1].id).toBe("test-uuid")

			// Manually set different IDs
			worker.portList[0].id = "port1"
			worker.portList[1].id = "port2"

			// Send refresh message
			await mockPort1.onmessage?.({ data: { type: "refresh" } })
			await Promise.resolve()
			await vi.advanceTimersByTimeAsync(0)

			// Verify other port receives notification
			expect(mockPort2.postMessage).toHaveBeenCalledWith({
				type: "reflectRefresh",
			})
			expect(mockPort1.postMessage).not.toHaveBeenCalled()
		})

		it("should handle unknown message type", () => {
			const mockPort = createMockPort()
			const event = { ports: [mockPort] }
			worker.onconnect(event)

			// Send message with unknown type
			mockPort.onmessage?.({ data: { type: "unknown" } })

			// Verify error message is broadcast
			expect(mockPort.postMessage).toHaveBeenCalledWith({
				type: "error",
				message: "Unknown message type",
			})
		})
	})

	describe("multiple ports handling", () => {
		it("should broadcast version updates to all ports", async () => {
			const mockPort1 = createMockPort()
			const mockPort2 = createMockPort()

			// Connect two ports
			worker.onconnect({ ports: [mockPort1] })
			worker.onconnect({ ports: [mockPort2] })

			// Wait for async operations to complete
			await Promise.resolve()

			// Send start message to the first port
			await mockPort1.onmessage?.({ data: { type: "start" } })

			// Verify all ports receive version updates
			expect(mockPort1.postMessage).toHaveBeenCalledWith({
				type: "reflectGetLatestVersion",
				data: "1.0.0",
			})
			expect(mockPort2.postMessage).toHaveBeenCalledWith({
				type: "reflectGetLatestVersion",
				data: "1.0.0",
			})
		})

		it("should maintain polling when some ports are still visible", async () => {
			const mockPort1 = createMockPort()
			const mockPort2 = createMockPort()

			// Connect two ports
			worker.onconnect({ ports: [mockPort1] })
			worker.onconnect({ ports: [mockPort2] })

			// Wait for async operations to complete
			await Promise.resolve()

			// Both ports send start message
			await mockPort1.onmessage?.({ data: { type: "start" } })
			await mockPort2.onmessage?.({ data: { type: "start" } })

			// First port sends stop message
			await mockPort1.onmessage?.({ data: { type: "stop" } })

			// Verify the timer is still running
			await vi.advanceTimersByTimeAsync(30000)
			expect(getLatestAppVersion).toHaveBeenCalledTimes(2)
		})
	})

	describe("error handling", () => {
		it("should handle getLatestAppVersion failure", async () => {
			const mockPort = createMockPort()
			const error = new Error("Failed to fetch version")
			vi.mocked(getLatestAppVersion).mockRejectedValueOnce(error)

			const event = { ports: [mockPort] }
			worker.onconnect(event)

			// Wait for async operations to complete
			await Promise.resolve()
			await vi.advanceTimersByTimeAsync(0)

			// Send start message
			await mockPort.onmessage?.({ data: { type: "start" } })
			await Promise.resolve()
			await vi.advanceTimersByTimeAsync(0)

			// Verify the error is handled correctly
			expect(mockPort.postMessage).toHaveBeenCalledWith({
				type: "reflectGetLatestVersion",
				data: undefined,
				message: error.message,
			})
		})
	})
})
