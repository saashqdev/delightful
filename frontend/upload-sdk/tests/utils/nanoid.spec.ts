import { describe, test, expect } from "vitest"
import { nanoid } from "../../src/utils/nanoid"

describe("nanoid", () => {
  test("should generate unique ID with specified length", () => {
    const id = nanoid(10);
    expect(id.length).toBe(10);
  });

  test("should generate 21 character length unique ID by default", () => {
    const id = nanoid();
    expect(id.length).toBe(21);
  });

  test("generated ID should only contain valid characters (letters, numbers, - or _)", () => {
    const id = nanoid(30);
    expect(id).toMatch(/^[a-zA-Z0-9\-_]+$/);
  });

  test("multiple calls should generate different IDs", () => {
    const ids = new Set();
    for (let i = 0; i < 100; i++) {
      ids.add(nanoid(15));
    }
    expect(ids.size).toBe(100); // All generated IDs should be unique
  });
});




