/**
 * @description Temporarily serves as the initialization entry point for all service layers, to be migrated in future architecture updates
 */
import { initializeApp } from "./initializeApp"

// Export singletons
export const { userService, loginService, configService, flowService } = initializeApp()
