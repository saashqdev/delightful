import { initializeApp as openSourceInitializeApp } from "@/opensource/services/initializeApp"

// Export singleton services
export const { userService, loginService, configService } = openSourceInitializeApp()
