# LoginServiceProvider

## Component Introduction

LoginServiceProvider is a React Context Provider component used to manage login service state and logic. It mainly handles login flows under different deployment environments (public cloud and private cloud) and provides related context data and methods for child components to use.

## Main Features

-   Manage login deployment environment (public cloud/private cloud)
-   Provide login service instance (LoginService) to child components
-   Manage cluster code (clusterCode) state
-   Provide environment switching capability

## Data Structure

```typescript
interface LoginServiceStore {
	// Login service instance
	service: LoginService
	// Current deployment environment type (public cloud/private cloud)
	deployment: LoginDeployment
	// Set deployment environment
	setDeployment: (deployment: LoginDeployment) => void
	// Cluster code (used for private deployment)
	clusterCode: string | null
	// Set cluster code
	setDeployCode: (clusterCode: string) => void
}
```

## Usage

### Basic Usage

```tsx
import { LoginServiceProvider } from "./LoginServiceProvider"
import { LoginService } from "@/services/user/LoginService"

// Create login service instance
const loginService = new LoginService(apis, serviceContainer)

function App() {
	return (
		<LoginServiceProvider service={loginService}>
			{/* Child components can get context through useLoginServiceContext */}
			<YourLoginComponent />
		</LoginServiceProvider>
	)
}
```

### Using HOC to Wrap Component

```tsx
import { withLoginService } from "./withLoginService"
import { LoginService } from "@/services/user/LoginService"

// Create login service instance
const loginService = new LoginService(apis, serviceContainer)

// Use HOC to wrap component
const WrappedComponent = withLoginService(YourComponent, loginService)

function App() {
	return <WrappedComponent />
}
```

### Using Context in Child Components

```tsx
import { useLoginServiceContext } from "./useLoginServiceContext"
import { LoginDeployment } from "@/pages/login/constants"

function LoginComponent() {
	const { service, deployment, setDeployment, clusterCode, setDeployCode } =
		useLoginServiceContext()

	const handleLogin = async () => {
		// Use service to perform login operation
		// ...
	}

	const switchToPrivateDeployment = () => {
		setDeployment(LoginDeployment.PrivateDeploymentLogin)
	}

	return (
		<div>
			{/* Render different login interfaces based on deployment environment */}
			{deployment === LoginDeployment.PublicDeploymentLogin ? (
				<PublicLoginForm onLogin={handleLogin} />
			) : (
				<PrivateLoginForm
					clusterCode={clusterCode}
					onSetClusterCode={setDeployCode}
					onLogin={handleLogin}
				/>
			)}

			<button onClick={switchToPrivateDeployment}>Switch to private deploy login</button>
		</div>
	)
}
```

## Notes

-   LoginServiceProvider should be placed at the top of the component tree that needs to access login service
-   Switching deployment environment will automatically handle clusterCode clearing and restoration
-   In private deploy mode, it will restore the last used clusterCode from cache
