import { describe, test, expect, beforeEach, vi } from "vitest"
import EventEmitter from "../../src/utils/EventEmitter"

describe("EventEmitter", () => {
  type TestListener = (message: string, count: number) => void
  let emitter: EventEmitter<TestListener>

  beforeEach(() => {
    emitter = new EventEmitter<TestListener>()
  })

  test("should be able to register event listeners", () => {
    const mockFn = vi.fn()
    emitter.on("testEvent", mockFn);
    expect(emitter.observers).toHaveProperty("testEvent")
    expect(emitter.observers.testEvent).toBe(mockFn)
  })

  test("should not duplicate register event listeners with same name", () => {
    const mockFn1 = vi.fn()
    const mockFn2 = vi.fn()
    emitter.on("testEvent", mockFn1)
    emitter.on("testEvent", mockFn2) // This should not be registered

    emitter.emit("testEvent", "hello", 123)
    expect(mockFn1).toHaveBeenCalledWith("hello", 123)
    expect(mockFn2).not.toHaveBeenCalled()
  })

  test("should be able to trigger event and execute listeners", () => {
    const mockFn = vi.fn()
    emitter.on("testEvent", mockFn)

    emitter.emit("testEvent", "test message", 42)
    expect(mockFn).toHaveBeenCalledTimes(1)
    expect(mockFn).toHaveBeenCalledWith("test message", 42)
  })

  test("for non-existent events, emit should not throw error", () => {
    expect(() => {
      emitter.emit("nonExistentEvent", "test", 123)
    }).not.toThrow()
  })

  test("should be able to remove event listeners", () => {
    const mockFn = vi.fn()
    emitter.on("testEvent", mockFn)
    emitter.off("testEvent")

    emitter.emit("testEvent", "test", 123)
    expect(mockFn).not.toHaveBeenCalled()
    expect(emitter.observers).not.toHaveProperty("testEvent")
  })

  test("removing non-existent event listener should not throw error", () => {
    expect(() => {
      emitter.off("nonExistentEvent")
    }).not.toThrow()
  })

  test("should be able to register multiple event listeners with different names", () => {
    const mockFn1 = vi.fn()
    const mockFn2 = vi.fn()

    emitter.on("event1", mockFn1)
    emitter.on("event2", mockFn2)

    emitter.emit("event1", "hello", 1)
    emitter.emit("event2", "world", 2)

    expect(mockFn1).toHaveBeenCalledWith("hello", 1)
    expect(mockFn2).toHaveBeenCalledWith("world", 2)
  })
})




