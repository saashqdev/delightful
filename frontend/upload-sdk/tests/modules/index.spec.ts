import { describe, it, expect, vi } from "vitest"
import { PlatformType } from "../../src"
import PlatformModules from "../../src/modules"

describe("Upload SDK module integration tests", () => {
	describe("Platform type enumeration", () => {
		it("should define all supported platform types", () => {
			expect(PlatformType.OSS).toBeDefined()
			expect(PlatformType.TOS).toBeDefined()
			expect(PlatformType.Kodo).toBeDefined()
			expect(PlatformType.OBS).toBeDefined()
		})
	})

	describe("Dynamic module loading", () => {
		it("should be able to load all platform modules", async () => {
			// Aliyun OSS module
			const OSSModule = PlatformModules[PlatformType.OSS]
			expect(OSSModule).toBeDefined()
			expect(typeof OSSModule.upload).toBe("function")
			expect(typeof OSSModule.defaultUpload).toBe("function")
			expect(typeof OSSModule.MultipartUpload).toBe("function")
			expect(typeof OSSModule.STSUpload).toBe("function")

			// Volcano TOS module
			const TOSModule = PlatformModules[PlatformType.TOS]
			expect(TOSModule).toBeDefined()
			expect(typeof TOSModule.upload).toBe("function")
			expect(typeof TOSModule.defaultUpload).toBe("function")
			expect(typeof TOSModule.MultipartUpload).toBe("function")
			expect(typeof TOSModule.STSUpload).toBe("function")

			// Qiniu Kodo module
			const KodoModule = PlatformModules[PlatformType.Kodo]
			expect(KodoModule).toBeDefined()
			expect(typeof KodoModule.upload).toBe("function")

			// Huawei OBS module
			const OBSModule = PlatformModules[PlatformType.OBS]
			expect(OBSModule).toBeDefined()
			expect(typeof OBSModule.upload).toBe("function")
			expect(typeof OBSModule.defaultUpload).toBe("function")
			expect(typeof OBSModule.MultipartUpload).toBe("function")
			expect(typeof OBSModule.STSUpload).toBe("function")
		})

		it("for unsupported platform types, PlatformModules should not include that type", () => {
			// Use a non-existent platform type
			const invalidPlatformType = "InvalidPlatform" as unknown as PlatformType

			// Check that PlatformModules does not contain this type
			expect(PlatformModules[invalidPlatformType]).toBeUndefined()
		})
	})

	describe("proxy module access", () => {
		it("should access all platform modules via proxy", async () => {
			// 检查所有平台代理是否存在
			expect(PlatformModules[PlatformType.OSS]).toBeDefined()
			expect(PlatformModules[PlatformType.TOS]).toBeDefined()
			expect(PlatformModules[PlatformType.Kodo]).toBeDefined()
			expect(PlatformModules[PlatformType.OBS]).toBeDefined()

			// 验证模块属性可访问
			const mockConsoleWarn = vi.spyOn(console, "warn").mockImplementation(() => {})
			const property = Object.keys(PlatformModules[PlatformType.OSS])[0]

			if (property) {
				// 访问属性会触发懒加载
				const propertyValue = PlatformModules[PlatformType.OSS][property]
				expect(propertyValue).toBeDefined()
			}

			mockConsoleWarn.mockRestore()
		})
	})
})
