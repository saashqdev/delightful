export type ServiceFactory<T = any> = (container: Container) => T | Promise<T>

export interface Container {
	registerFactory<T>(name: string, factory: ServiceFactory<T>): Container
	register<T>(name: string, instance: T): Container
	get<T>(name: string): T
}

export class ServiceContainer implements Container {
	private services: Record<string, any> = {}

	private factories: Record<string, ServiceFactory> = {}

	// Register service factory
	registerFactory<T>(name: string, factory: ServiceFactory<T>): Container {
		this.factories[name] = factory
		return this
	}

	// Register service instance directly
	register<T>(name: string, instance: T): Container {
		this.services[name] = instance
		return this
	}

	// Get service instance (supports lazy loading)
	get<T>(name: string): T {
		if (!this.services[name] && this.factories[name]) {
			this.services[name] = this.factories[name](this)
		}

		if (!this.services[name]) {
			throw new Error(`Service "${name}" is not registered`)
		}

		return this.services[name] as T
	}

	// Check if service is registered
	has(name: string): boolean {
		return name in this.services || name in this.factories
	}

	// Reset container
	reset(): void {
		this.services = {}
	}

	// Remove specific service
	remove(name: string): void {
		delete this.services[name]
	}
}
