# LoginServiceProvider

## component介绍

LoginServiceProvider isone个 React Context Provider component，用inmanageloginservice的status和logic。它main负责handledifferentdeployenvironment（公have云和private云）下的loginflow，并提供related的上下文data和method给子component使用。

## mainfunctionality

-   managelogindeployenvironment（公have云/private云）
-   提供loginserviceinstance（LoginService）给子component
-   managecluster代码（clusterCode）status
-   提供environment切换能力

## data结构

```typescript
interface LoginServiceStore {
	// loginserviceinstance
	service: LoginService
	// when前deployenvironmentclass型（公have云/private云）
	deployment: LoginDeployment
	// settingsdeployenvironment
	setDeployment: (deployment: LoginDeployment) => void
	// cluster代码（privatedeploytime使用）
	clusterCode: string | null
	// settingscluster代码
	setDeployCode: (clusterCode: string) => void
}
```

## 使用方式

### 基本使用

```tsx
import { LoginServiceProvider } from "./LoginServiceProvider"
import { LoginService } from "@/services/user/LoginService"

// createloginserviceinstance
const loginService = new LoginService(apis, serviceContainer)

function App() {
	return (
		<LoginServiceProvider service={loginService}>
			{/* 子componentcanthrough useLoginServiceContext get上下文 */}
			<YourLoginComponent />
		</LoginServiceProvider>
	)
}
```

### 使用 HOC package装component

```tsx
import { withLoginService } from "./withLoginService"
import { LoginService } from "@/services/user/LoginService"

// createloginserviceinstance
const loginService = new LoginService(apis, serviceContainer)

// 使用 HOC package装component
const WrappedComponent = withLoginService(YourComponent, loginService)

function App() {
	return <WrappedComponent />
}
```

### at子component中使用上下文

```tsx
import { useLoginServiceContext } from "./useLoginServiceContext"
import { LoginDeployment } from "@/pages/login/constants"

function LoginComponent() {
	const { service, deployment, setDeployment, clusterCode, setDeployCode } =
		useLoginServiceContext()

	const handleLogin = async () => {
		// 使用 service 进行loginoperation
		// ...
	}

	const switchToPrivateDeployment = () => {
		setDeployment(LoginDeployment.PrivateDeploymentLogin)
	}

	return (
		<div>
			{/* 根据deployenvironmentrenderdifferent的logininterface */}
			{deployment === LoginDeployment.PublicDeploymentLogin ? (
				<PublicLoginForm onLogin={handleLogin} />
			) : (
				<PrivateLoginForm
					clusterCode={clusterCode}
					onSetClusterCode={setDeployCode}
					onLogin={handleLogin}
				/>
			)}

			<button onClick={switchToPrivateDeployment}>切换toprivatedeploylogin</button>
		</div>
	)
}
```

## note事项

-   LoginServiceProvider 应when放置atneed访问loginservice的component树的top
-   切换deployenvironmenttime会自动handle clusterCode 的清除和resume
-   atprivatedeploypattern下，会fromcache中resume上次使用的 clusterCode
