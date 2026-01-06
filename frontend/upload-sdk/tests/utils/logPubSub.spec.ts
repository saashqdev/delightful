import { describe, test, expect, beforeEach, vi } from "vitest"
import logPubSub from "../../src/utils/logPubSub"
import { LogModule } from "../../src/types/log"

describe("logPubSub", () => {
  beforeEach(() => {
    // Clear all subscribers
    // @ts-ignore - Access private property for testing
    logPubSub.callbacks = []
  })

  describe("subscribe method", () => {
    test("should be able to add subscribers", () => {
      const mockCallback = vi.fn()
      logPubSub.subscribe(mockCallback)

      // @ts-ignore - Access private property for testing
      expect(logPubSub.callbacks.length).toBe(1)
      // @ts-ignore
      expect(logPubSub.callbacks[0]).toBe(mockCallback)
    })

    test("should be able to add multiple subscribers", () => {
      const mockCallback1 = vi.fn()
      const mockCallback2 = vi.fn()
      const mockCallback3 = vi.fn()

      logPubSub.subscribe(mockCallback1)
      logPubSub.subscribe(mockCallback2)
      logPubSub.subscribe(mockCallback3)

      // @ts-ignore - Access private property for testing
      expect(logPubSub.callbacks.length).toBe(3)
    })
  })

  describe("report method", () => {
    test("should call all subscribed callback functions", () => {
      const mockCallback1 = vi.fn()
      const mockCallback2 = vi.fn()

      logPubSub.subscribe(mockCallback1)
      logPubSub.subscribe(mockCallback2)

      const logConfig: LogModule.CreateLogConfig = {
        type: "DEBUG",
        eventName: "upload",
        eventParams: "test_params",
      }

      logPubSub.report(logConfig)

      expect(mockCallback1).toHaveBeenCalledTimes(1)
      expect(mockCallback2).toHaveBeenCalledTimes(1)
    })

    test("should not have errors when there are no subscribers", () => {
      expect(() => {
        logPubSub.report({
          type: "DEBUG",
          eventName: "upload",
        })
      }).not.toThrow()
    })

    test("should pass correct log data to callback function", () => {
      const mockCallback = vi.fn()
      logPubSub.subscribe(mockCallback)

      const logConfig: LogModule.CreateLogConfig = {
        type: "DEBUG",
        eventName: "upload",
        eventParams: "test_params",
        eventResponse: "test_response",
        extra: "test_extra",
      }

      logPubSub.report(logConfig)

      expect(mockCallback).toHaveBeenCalledWith(
        expect.objectContaining({
          type: "DEBUG",
          event_name: "upload",
          event_params: "test_params",
          event_response: "test_response",
          extra: "test_extra",
        }),
      )
    })
  })

  describe("createLog static method", () => {
    test("should correctly create a normal log object", () => {
      const logConfig: LogModule.CreateLogConfig = {
        type: "DEBUG",
        eventName: "upload",
        eventParams: "test_params",
        eventResponse: "test_response",
        extra: "test_extra",
      }

      // @ts-ignore - Access static method
      const log = logPubSub.constructor.createLog(logConfig)

      expect(log).toEqual(
        expect.objectContaining({
          type: "DEBUG",
          event_name: "upload",
          event_params: "test_params",
          event_response: "test_response",
          extra: "test_extra",
          exception_type: "",
          exception_message: "",
          exception_file: "",
          exception_line: "",
          exception_row: "",
        }),
      )

      expect(log.time).toBeInstanceOf(Date)
      expect(log.version).toBe("Upload-SDK.js VERSION")
    })

    test("should correctly create an error log object", () => {
      const error = new Error("Test error message")
      error.stack =
        "Error: Test error message\n at TestFunction (test.ts:123:45)"

      const logConfig: LogModule.CreateLogConfig = {
        type: "ERROR",
        eventName: "download",
        error,
      }

      // @ts-ignore - Access static method
      const log = logPubSub.constructor.createLog(logConfig)

      expect(log).toEqual(
        expect.objectContaining({
          type: "ERROR",
          event_name: "download",
          exception_type: "Error",
          exception_message: "Test error message",
          exception_file: "test.ts",
          exception_line: "123",
          exception_row: "45",
        }),
      )
    })

    test("should use default values to fill optional fields", () => {
      const logConfig: LogModule.CreateLogConfig = {
        type: "WARN",
        eventName: "upload",
      }

      // @ts-ignore - Access static method
      const log = logPubSub.constructor.createLog(logConfig)

      expect(log).toEqual(
        expect.objectContaining({
          event_params: "",
          event_response: "",
          extra: "",
        }),
      )
    })
  })
})




